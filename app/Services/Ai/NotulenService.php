<?php

namespace App\Services\Ai;

use App\Models\AiJob;

/** Notulen rapat: segmentasi transkrip + ekstraksi keputusan & action items. */
class NotulenService
{
    /** @return array{segmen: list<string>, keputusan: list<string>, aksi: list<string>} */
    public function notulen(string $transkrip, ?int $userId = null): array
    {
        $baris = array_values(array_filter(array_map('trim', preg_split('/\R+/u', $transkrip) ?: [])));
        $keputusan = [];
        $aksi = [];

        foreach ($baris as $b) {
            $l = mb_strtolower($b);
            if (str_contains($l, 'putus') || str_contains($l, 'sepakat') || str_contains($l, 'setuju') || str_starts_with($l, 'keputusan')) {
                $keputusan[] = $b;
            }
            if (str_contains($l, 'tindak lanjut') || str_contains($l, 'action') || str_contains($l, 'tugas') || str_contains($l, 'deadline') || str_contains($l, 'mohon') || str_contains($l, 'diminta')) {
                $aksi[] = $b;
            }
        }

        $hasil = ['segmen' => $baris, 'keputusan' => array_values($keputusan), 'aksi' => array_values($aksi)];

        try {
            AiJob::create([
                'user_id' => $userId, 'jenis' => 'transkrip',
                'input' => mb_substr($transkrip, 0, 2000), 'output' => json_encode($hasil),
                'status' => 'selesai', 'provider' => 'aturan',
            ]);
        } catch (\Throwable) {
        }

        return $hasil;
    }
}
