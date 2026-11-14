<?php

namespace App\Services;

use App\Models\Aset;
use Illuminate\Support\Facades\DB;

/**
 * M-036: penyusutan garis lurus aset.
 * Susut bulanan = nilai / umur_manfaat_bulan, dibatasi nilai buku ≥ 0.
 * Idempoten per bulan via terakhir_susut_at (rerun bulan sama = 0).
 */
class AsetService
{
    public static function susutBulanan(Aset $aset): int
    {
        $umur = max(1, (int) $aset->umur_manfaat_bulan);

        return (int) floor((int) $aset->nilai / $umur);
    }

    public static function nilaiBuku(Aset $aset): int
    {
        return max(0, (int) $aset->nilai - (int) $aset->akumulasi_penyusutan);
    }

    /**
     * Susutkan 1 bulan berjalan. Return susut yang dibukukan (0 bila sudah
     * disusutkan bulan ini atau nilai buku habis).
     */
    public static function susutkan(Aset $aset, ?string $bulan = null): int
    {
        return DB::transaction(function () use ($aset, $bulan): int {
            $locked = Aset::whereKey($aset->id)->lockForUpdate()->firstOrFail();
            $bulan ??= now()->format('Y-m');

            $terakhir = $locked->terakhir_susut_at?->format('Y-m');

            if ($terakhir === $bulan) {
                return 0;
            }

            $susut = min(self::susutBulanan($locked), self::nilaiBuku($locked));

            if ($susut <= 0) {
                return 0;
            }

            $locked->update([
                'akumulasi_penyusutan' => (int) $locked->akumulasi_penyusutan + $susut,
                'terakhir_susut_at' => now()->toDateString(),
            ]);

            return $susut;
        });
    }
}
