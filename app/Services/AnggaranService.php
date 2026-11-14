<?php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\JurnalKeuangan;

/**
 * M-033: anggaran vs realisasi.
 * Realisasi = jurnal penerimaan − refund pada tagihan berjenis = kode
 * anggaran dan tahun akademik yang sama.
 */
class AnggaranService
{
    /**
     * @return array{realisasi: int, sisa: int, persen: float}
     */
    public static function realisasi(Anggaran $anggaran): array
    {
        try {
            $masuk = (int) JurnalKeuangan::join('tagihan', 'tagihan.id', '=', 'jurnal_keuangan.tagihan_id')
                ->where('jurnal_keuangan.jenis', 'penerimaan')
                ->where('tagihan.tahun_akademik_id', $anggaran->tahun_akademik_id)
                ->where('tagihan.jenis', $anggaran->kode)
                ->sum('jurnal_keuangan.nominal');

            $keluar = (int) JurnalKeuangan::join('tagihan', 'tagihan.id', '=', 'jurnal_keuangan.tagihan_id')
                ->where('jurnal_keuangan.jenis', 'refund')
                ->where('tagihan.tahun_akademik_id', $anggaran->tahun_akademik_id)
                ->where('tagihan.jenis', $anggaran->kode)
                ->sum('jurnal_keuangan.nominal');
        } catch (\Throwable) {
            return ['realisasi' => 0, 'sisa' => (int) $anggaran->pagu, 'persen' => 0.0];
        }

        $realisasi = max(0, $masuk - $keluar);
        $pagu = (int) $anggaran->pagu;

        return [
            'realisasi' => $realisasi,
            'sisa' => $pagu - $realisasi,
            'persen' => $pagu > 0 ? round($realisasi / $pagu * 100, 1) : 0.0,
        ];
    }

    /** Ringkasan semua anggaran satu periode (1 query agregat per jenis jurnal). */
    public static function ringkasanPeriode(int $tahunAkademikId): array
    {
        $out = [];

        foreach (Anggaran::where('tahun_akademik_id', $tahunAkademikId)->orderBy('kode')->get() as $anggaran) {
            $out[] = ['anggaran' => $anggaran] + self::realisasi($anggaran);
        }

        return $out;
    }
}
