<?php

namespace App\Services;

use App\Models\SkripsiJudul;

class SidangSyaratService
{
    /** Syarat lunak: minimal bimbingan disetujui per jenis sidang. */
    public const MIN_BIMBINGAN = [
        'proposal' => 2,
        'hasil' => 4,
        'tutup' => 6,
    ];

    /**
     * @return array{lolos: bool, keras_lolos: bool, items: array<int, array{label: string, ok: bool, keras: bool}>, warnings: string[]}
     */
    public static function check(SkripsiJudul $judul, string $jenis = 'proposal'): array
    {
        $min = self::MIN_BIMBINGAN[$jenis] ?? 0;

        $pembimbingOk = $judul->status === 'disetujui' || $judul->status === 'sidang'
            || ($judul->pembimbing1_id !== null && $judul->status !== 'ditolak');
        $keuanganOk = ! KeuanganService::isBlockedForSidang($judul->mahasiswa_id);
        $bimbinganDisetujui = $judul->bimbingans()->where('status', 'disetujui')->count();
        $bimbinganOk = $bimbinganDisetujui >= $min;

        $plag = $judul->plagiarisme_status;
        $plagiarismeOk = $plag === null || $plag === 'lolos';

        $items = [
            ['label' => 'Pembimbing ditetapkan & judul disetujui', 'ok' => $pembimbingOk, 'keras' => true],
            ['label' => 'Bebas tunggakan keuangan', 'ok' => $keuanganOk, 'keras' => true],
            ['label' => "Bimbingan disetujui {$bimbinganDisetujui}/{$min}", 'ok' => $bimbinganOk, 'keras' => false],
            ['label' => 'Plagiarisme '.($plag ?? 'belum dicek'), 'ok' => $plagiarismeOk, 'keras' => false],
        ];

        $kerasLolos = $pembimbingOk && $keuanganOk;
        $warnings = [];
        foreach ($items as $item) {
            if (! $item['ok'] && ! $item['keras']) {
                $warnings[] = 'Perhatian: '.$item['label'].'.';
            }
        }

        return ['lolos' => $kerasLolos, 'keras_lolos' => $kerasLolos, 'items' => $items, 'warnings' => $warnings];
    }
}
