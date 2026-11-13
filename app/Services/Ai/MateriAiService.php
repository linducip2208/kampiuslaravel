<?php

namespace App\Services\Ai;

use App\Models\AiJob;

/** Ringkasan ekstraktif + generator quiz pilihan ganda dari teks materi. */
class MateriAiService
{
    /** @return array{ringkasan: string, poin: list<string>} */
    public function ringkas(string $teks, ?int $userId = null, int $maksKalimat = 5): array
    {
        $kalimat = preg_split('/(?<=[.!?])\s+/u', trim(preg_replace('/\s+/u', ' ', $teks) ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($kalimat) === 0) {
            return ['ringkasan' => '', 'poin' => []];
        }

        $freq = [];
        foreach ($kalimat as $k) {
            foreach (preg_split('/[^a-zA-Z\x{00C0}-\x{024F}]+/u', mb_strtolower($k), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
                if (mb_strlen($w) >= 4) {
                    $freq[$w] = ($freq[$w] ?? 0) + 1;
                }
            }
        }

        $skor = [];
        foreach ($kalimat as $i => $k) {
            $s = 0;
            foreach (preg_split('/[^a-zA-Z\x{00C0}-\x{024F}]+/u', mb_strtolower($k), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
                $s += $freq[$w] ?? 0;
            }
            $skor[$i] = $s / max(1, str_word_count($k));
        }
        arsort($skor);
        $pilih = array_slice(array_keys($skor), 0, min($maksKalimat, count($kalimat)));
        sort($pilih);

        $poin = array_map(fn ($i) => $kalimat[$i], $pilih);
        $hasil = ['ringkasan' => implode(' ', $poin), 'poin' => $poin];

        try {
            AiJob::create([
                'user_id' => $userId, 'jenis' => 'ringkas',
                'input' => mb_substr($teks, 0, 2000), 'output' => json_encode($hasil),
                'status' => 'selesai', 'provider' => 'ekstraktif',
            ]);
        } catch (\Throwable) {
        }

        return $hasil;
    }

    /** @return list<array{pertanyaan: string, opsi: list<string>, jawaban: int}> */
    public function buatQuiz(string $teks, int $jumlah = 3): array
    {
        $r = $this->ringkas($teks);
        $quiz = [];

        foreach (array_slice($r['poin'], 0, $jumlah) as $p) {
            $words = preg_split('/\s+/u', $p, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $panjang = array_values(array_filter($words, fn ($w) => mb_strlen(trim($w, ',. ')) >= 5));
            if (count($panjang) === 0) {
                continue;
            }
            $kunci = trim($panjang[intdiv(count($panjang), 2)], ',. ');
            $distraktor = ['konsep tersebut', 'pendekatan lain', 'faktor eksternal'];
            $opsi = [$kunci, ...$distraktor];
            // Acak deterministik berbasis hash agar stabil di test.
            $h = crc32($p);
            $urutan = [0, 1, 2, 3];
            usort($urutan, fn ($a, $b) => (($h >> ($a * 3)) & 7) <=> (($h >> ($b * 3)) & 7));
            $opsiAcak = array_map(fn ($i) => $opsi[$i], $urutan);

            $quiz[] = [
                'pertanyaan' => 'Istilah kunci yang tepat untuk melengkapi: "'.str_ireplace($kunci, '____', $p).'"?',
                'opsi' => array_values($opsiAcak),
                'jawaban' => array_search($kunci, $opsiAcak, true),
            ];
        }

        return $quiz;
    }
}
