<?php

namespace App\Services\Ai;

use App\Models\KnowledgeChunk;

/**
 * M-057: guardrail RAG — tolak prompt-injection + redaksi PII pada output.
 * Aturan eksplisit & teruji (bukan klaim "AI aman").
 */
class RagGuardrail
{
    /** Pola instruksi jahat (case-insensitive). */
    public const INJEKSI = [
        'abaikan instruksi',
        'ignore previous instructions',
        'ignore all instructions',
        'system prompt',
        'tampilkan system prompt',
        'jailbreak',
        'bypass filter',
        'lepas filter',
    ];

    /**
     * @return array{lolos: bool, alasan: string}
     */
    public static function saringMasukan(string $teks): array
    {
        $lower = mb_strtolower($teks);

        foreach (self::INJEKSI as $pola) {
            if (str_contains($lower, $pola)) {
                return ['lolos' => false, 'alasan' => "Pola ditolak: '{$pola}'."];
            }
        }

        if (mb_strlen(trim($teks)) < 3) {
            return ['lolos' => false, 'alasan' => 'Pertanyaan terlalu pendek.'];
        }

        return ['lolos' => true, 'alasan' => ''];
    }

    /** Redaksi PII: NIK 16 digit, HP 09xx/08xx/62, email. */
    public static function redaksiPii(string $teks): string
    {
        $teks = (string) preg_replace('/\b\d{16}\b/', '[NIK DIREDaksi]', $teks);
        $teks = (string) preg_replace('/\b(?:\+?62|0)8\d{8,11}\b/', '[HP DIREDaksi]', $teks);
        $teks = (string) preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[EMAIL DIREDaksi]', $teks);

        return $teks;
    }

    /** Skor 0..n = jumlah kata kunci pertanyaan yang muncul di chunk. */
    public static function skor(string $pertanyaan, string $teks): int
    {
        $kata = array_unique(array_filter(
            array_map(
                fn ($w) => mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]+/u', '', $w)),
                preg_split('/\s+/u', $pertanyaan) ?: []
            ),
            fn ($w) => mb_strlen($w) >= 4
        ));

        if ($kata === []) {
            return 0;
        }

        $lower = mb_strtolower($teks);
        $skor = 0;

        foreach ($kata as $w) {
            if (str_contains($lower, $w)) {
                $skor++;
            }
        }

        return $skor;
    }

    /**
     * @return list<array{id: int, judul: string, teks: string, skor: int}>
     */
    public static function relevan(string $pertanyaan, int $limit = 3, ?int $prodiId = null): array
    {
        $limit = max(1, min($limit, 5));
        $hasil = [];

        foreach (KnowledgeChunk::with('dokumen')->cursor() as $chunk) {
            if (! $chunk->dokumen || ! $chunk->dokumen->aktif) {
                continue;
            }

            // Filter prodi: dokumen global (null) terlihat semua; dokumen
            // ber-prodi hanya untuk prodi yang sama.
            $dokProdi = $chunk->dokumen->prodi_id;

            if ($dokProdi !== null && $prodiId !== null && (int) $dokProdi !== $prodiId) {
                continue;
            }

            $skor = self::skor($pertanyaan, (string) $chunk->teks);

            if ($skor > 0) {
                $hasil[] = [
                    'id' => $chunk->id,
                    'judul' => (string) $chunk->dokumen->judul,
                    'teks' => (string) $chunk->teks,
                    'skor' => $skor,
                ];
            }
        }

        usort($hasil, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        return array_slice($hasil, 0, $limit);
    }
}
