<?php

namespace App\Services\Ai;

use App\Models\AiJob;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Services\AiKuotaService;
use Illuminate\Support\Facades\DB;

/**
 * M-057: RAG knowledge-base — ingest (chunking) + retrieval (skor kata kunci)
 * + jawab via driver AI + guardrail + kuota.
 */
class RagService
{
    public const CHUNK = 800;

    /** Batas total karakter konteks yang diteruskan ke driver AI. */
    public const MAX_KONTEKS = 4000;

    /** Pecah konten menjadi chunk ±800 karakter per batas kalimat. */
    public static function ingest(string $judul, string $konten, ?string $sumber = null, ?int $prodiId = null): KnowledgeBase
    {
        return DB::transaction(function () use ($judul, $konten, $sumber, $prodiId): KnowledgeBase {
            $dok = KnowledgeBase::create([
                'judul' => $judul, 'konten' => $konten, 'sumber' => $sumber, 'aktif' => true,
                'prodi_id' => $prodiId,
            ]);

            $potong = preg_split('/(?<=[.!?\n])\s+/u', $konten) ?: [$konten];
            $buffer = '';
            $urutan = 0;

            $simpan = function () use ($dok, &$buffer, &$urutan): void {
                $teks = trim($buffer);

                if ($teks !== '') {
                    KnowledgeChunk::create(['kb_id' => $dok->id, 'urutan' => $urutan++, 'teks' => $teks]);
                }

                $buffer = '';
            };

            foreach ($potong as $kalimat) {
                if (mb_strlen($buffer) + mb_strlen($kalimat) > self::CHUNK && trim($buffer) !== '') {
                    $simpan();
                }
                $buffer .= ($buffer === '' ? '' : ' ').$kalimat;
            }
            $simpan();

            return $dok->fresh();
        });
    }

    /**
     * Evaluasi deterministik (tanpa panggil AI): untuk tiap pasangan
     * [tanya, harap], ambil chunk teratas lalu skor F1 tumpang-tindih kata
     * (≥4 huruf) antara chunk dan jawaban harapan.
     *
     * @param  list<array{tanya: string, harap: string}>  $pasangan
     * @return array{skor: float, detail: list<array{tanya: string, skor: float, sumber: list<string>}>}
     */
    public static function evaluasi(array $pasangan, ?int $prodiId = null): array
    {
        $detail = [];

        foreach ($pasangan as $p) {
            $tanya = (string) ($p['tanya'] ?? '');
            $harap = (string) ($p['harap'] ?? '');
            $relevan = RagGuardrail::relevan($tanya, 1, $prodiId);
            $teks = (string) ($relevan[0]['teks'] ?? '');
            $skor = self::skorKecocokan($teks, $harap);

            $detail[] = [
                'tanya' => $tanya,
                'skor' => $skor,
                'sumber' => $relevan === [] ? [] : [($relevan[0]['judul'] ?? '')],
            ];
        }

        $rata = $detail === [] ? 0.0 : round(array_sum(array_column($detail, 'skor')) / count($detail), 3);

        return ['skor' => $rata, 'detail' => $detail];
    }

    /** F1 tumpang-tindih kata (case-insensitive, kata ≥4 huruf). */
    public static function skorKecocokan(string $a, string $b): float
    {
        $kata = fn (string $s): array => array_values(array_unique(array_filter(
            preg_split('/[^a-z0-9]+/u', mb_strtolower($s)) ?: [],
            fn ($w) => mb_strlen((string) $w) >= 4
        )));

        $ka = $kata($a);
        $kb = $kata($b);

        if ($ka === [] || $kb === []) {
            return 0.0;
        }

        $iris = count(array_intersect($ka, $kb));
        $presisi = $iris / count($ka);
        $recall = $iris / count($kb);

        if ($presisi + $recall <= 0) {
            return 0.0;
        }

        return round(2 * $presisi * $recall / ($presisi + $recall), 3);
    }

    public static function jawab(string $pertanyaan, ?int $userId = null, ?int $prodiId = null): array
    {
        $saring = RagGuardrail::saringMasukan($pertanyaan);

        if (! $saring['lolos']) {
            return ['jawaban' => 'Maaf, pertanyaan ditolak guardrail: '.$saring['alasan'], 'sumber' => [], 'ditolak' => true, 'alasan' => $saring['alasan']];
        }

        $relevan = RagGuardrail::relevan($pertanyaan, 3, $prodiId);

        if ($relevan === []) {
            $jawaban = 'Basis pengetahuan belum memuat jawaban untuk pertanyaan itu. Hubungi admin akademik.';
            self::catat($userId, 'rag', $pertanyaan, $jawaban, 'simulated');

            return ['jawaban' => $jawaban, 'sumber' => [], 'ditolak' => false, 'alasan' => ''];
        }

        $konteks = implode("\n---\n", array_map(
            fn ($r, $i) => '[S'.$i.'] '.$r['judul'].': '.$r['teks'],
            $relevan,
            array_keys($relevan)
        ));

        // Batas konteks: potong agar prompt tak membengkak; sumber lengkap
        // tetap dikembalikan agar jawaban bisa ditelusur.
        if (mb_strlen($konteks) > self::MAX_KONTEKS) {
            $konteks = mb_substr($konteks, 0, self::MAX_KONTEKS)."\n[…konteks dipotong]";
        }

        $messages = [
            ['role' => 'system', 'content' => 'Jawab HANYA dari konteks berikut. Bila tak ada di konteks, katakan tidak tahu. Konteks: '.$konteks],
            ['role' => 'user', 'content' => $pertanyaan],
        ];

        try {
            $jawaban = AiProvider::chat($messages);
            $provider = (string) config('ai.default_driver', 'simulated');
        } catch (\Throwable $e) {
            $jawaban = 'Layanan AI tidak tersedia: '.$e->getMessage();
            $provider = 'error';
        }

        $jawaban = RagGuardrail::redaksiPii($jawaban);
        self::catat($userId, 'rag', $pertanyaan, $jawaban, $provider);
        AiKuotaService::catat($userId, 'rag');

        return [
            'jawaban' => $jawaban,
            'sumber' => array_values(array_unique(array_column($relevan, 'judul'))),
            'ditolak' => false,
            'alasan' => '',
        ];
    }

    private static function catat(?int $userId, string $jenis, string $input, string $output, string $provider): void
    {
        try {
            AiJob::create([
                'user_id' => $userId, 'jenis' => $jenis,
                'input' => mb_substr($input, 0, 4000),
                'output' => mb_substr($output, 0, 4000),
                'status' => 'selesai', 'provider' => $provider,
            ]);
        } catch (\Throwable) {
        }
    }
}
