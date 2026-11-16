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
     * @return array{jawaban: string, sumber: list<string>, ditolak: bool, alasan: string}
     */
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
