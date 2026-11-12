<?php

namespace App\Services;

use App\Models\Nilai;
use Illuminate\Support\Collection;

/**
 * Perhitungan akademik terpusat (satu sumber kebenaran).
 * IPK = Σ(bobot × SKS) / Σ(SKS). IP = sama per tahun akademik.
 */
class AkademikService
{
    /**
     * @return array{ipk: float, total_sks: int, per_tahun: array<int, array{ip: float, sks: int}>, nilai: Collection}
     */
    public static function ringkasan(int $mahasiswaId): array
    {
        $nilai = Nilai::with('kelas.matakuliah', 'kelas.tahunAkademik')
            ->where('mahasiswa_id', $mahasiswaId)
            ->get();

        $totalBobot = 0.0;
        $totalSks = 0;
        $perTahun = [];

        foreach ($nilai as $row) {
            $sks = (int) ($row->kelas->matakuliah->sks ?? 0);
            $tahunId = (int) ($row->kelas->tahun_akademik_id ?? 0);

            $totalBobot += (float) $row->bobot * $sks;
            $totalSks += $sks;

            $perTahun[$tahunId] ??= ['ip' => 0.0, 'sks' => 0, 'bobot' => 0.0];
            $perTahun[$tahunId]['bobot'] += (float) $row->bobot * $sks;
            $perTahun[$tahunId]['sks'] += $sks;
        }

        foreach ($perTahun as $tahunId => $aggr) {
            $perTahun[$tahunId] = [
                'ip' => $aggr['sks'] > 0 ? round($aggr['bobot'] / $aggr['sks'], 2) : 0.0,
                'sks' => $aggr['sks'],
            ];
        }

        return [
            'ipk' => $totalSks > 0 ? round($totalBobot / $totalSks, 2) : 0.0,
            'total_sks' => $totalSks,
            'per_tahun' => $perTahun,
            'nilai' => $nilai,
        ];
    }

    public static function batasSks(float $ipk): int
    {
        return match (true) {
            $ipk >= 3.0 => 24,
            $ipk >= 2.5 => 21,
            $ipk >= 2.0 => 18,
            default => 15,
        };
    }
}
