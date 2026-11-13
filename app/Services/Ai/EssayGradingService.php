<?php

namespace App\Services\Ai;

use App\Models\AiJob;

/**
 * Auto-grading essay: skor rubrik deterministik (konten/struktur/bahasa)
 * berbasis cakupan kata kunci + panjang + struktur paragraf. LLM opsional
 * hanya memperhalus feedback, tidak mengubah skor inti.
 */
class EssayGradingService
{
    /** @return array{skor: float, huruf: string, rincian: array<string, float>, feedback: string} */
    public function nilai(string $jawaban, string $kunciJawaban, ?int $userId = null): array
    {
        $kataKunci = $this->kataKunci($kunciJawaban);
        $teks = mb_strtolower($jawaban);

        $kena = 0;
        foreach ($kataKunci as $k) {
            if (str_contains($teks, $k)) {
                $kena++;
            }
        }
        $konten = count($kataKunci) > 0 ? round($kena / count($kataKunci) * 100, 1) : 50.0;

        $kata = str_word_count($jawaban);
        $paragraf = count(array_filter(preg_split('/\R{2,}|\R/', $jawaban)));
        $struktur = min(100, round($paragraf * 25 + min(25, $kata / 8), 1));
        $kalimat = preg_split('/[.!?]+/', $jawaban, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $rata = count($kalimat) > 0 ? $kata / count($kalimat) : 0;
        $bahasa = $rata >= 5 && $rata <= 30 ? 85.0 : 60.0;

        $skor = round(0.6 * $konten + 0.25 * $struktur + 0.15 * $bahasa, 1);
        $huruf = $skor >= 85 ? 'A' : ($skor >= 70 ? 'B' : ($skor >= 55 ? 'C' : ($skor >= 40 ? 'D' : 'E')));

        $feedback = "Konten {$konten} (kata kunci terpenuhi {$kena}/".count($kataKunci).'), '
            ."struktur {$struktur}, bahasa {$bahasa}. "
            .($skor < 70 ? 'Tambahkan argumen + contoh konkret dari materi.' : 'Jawaban baik, pertahankan kedalaman analisis.');

        $hasil = ['skor' => $skor, 'huruf' => $huruf, 'rincian' => ['konten' => $konten, 'struktur' => (float) $struktur, 'bahasa' => $bahasa], 'feedback' => $feedback];

        try {
            AiJob::create([
                'user_id' => $userId, 'jenis' => 'grade',
                'input' => mb_substr($jawaban, 0, 2000), 'output' => json_encode($hasil),
                'status' => 'selesai', 'provider' => AiProvider::driverName(),
            ]);
        } catch (\Throwable) {
        }

        return $hasil;
    }

    /** @return list<string> */
    private function kataKunci(string $kunci): array
    {
        $stop = ['dan', 'yang', 'untuk', 'dengan', 'adalah', 'dari', 'dalam', 'pada', 'sebagai', 'atau', 'ini', 'itu', 'oleh'];
        $words = preg_split('/[^a-zA-Z\x{00C0}-\x{024F}]+/u', mb_strtolower($kunci), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $freq = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 4 || in_array($w, $stop, true)) {
                continue;
            }
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
        arsort($freq);

        return array_slice(array_keys($freq), 0, 10);
    }
}
