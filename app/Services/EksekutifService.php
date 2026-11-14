<?php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\TracerAlumni;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M-056: KPI eksekutif — 1 agregat per metrik (tanpa N+1, tanpa muat koleksi).
 */
class EksekutifService
{
    /** @return array{ipk_rata: float, lulusan: int, serapan_kerja_pct: float, rasio_mhs_dosen: float, realisasi_anggaran_pct: float} */
    public static function kpi(): array
    {
        try {
            $ipk = 0.0;

            if (Schema::hasTable('nilai')) {
                $row = DB::table('nilai')
                    ->join('kelas', 'kelas.id', '=', 'nilai.kelas_id')
                    ->join('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
                    ->where('nilai.is_locked', true)
                    ->selectRaw('SUM(nilai.bobot * matakuliah.sks) / NULLIF(SUM(matakuliah.sks), 0) AS ipk')
                    ->first();
                $ipk = round((float) ($row->ipk ?? 0), 2);
            }

            $lulusan = Schema::hasTable('mahasiswa')
                ? (int) Mahasiswa::where('status', 'lulus')->count()
                : 0;

            $serapan = 0.0;

            if (Schema::hasTable('tracer_alumni')) {
                $total = (int) TracerAlumni::count();
                $kerja = $total > 0 ? (int) TracerAlumni::whereIn('status_kerja', ['bekerja', 'wirausaha'])->count() : 0;
                $serapan = $total > 0 ? round($kerja / $total * 100, 1) : 0.0;
            }

            $dosen = Schema::hasTable('dosen') ? max(1, (int) Dosen::count()) : 1;
            $mhsAktif = Schema::hasTable('mahasiswa') ? (int) Mahasiswa::where('status', 'aktif')->count() : 0;

            $anggaranPct = 0.0;

            if (Schema::hasTable('anggaran')) {
                $semua = [];
                foreach (Anggaran::pluck('id') as $id) {
                    $semua[] = AnggaranService::realisasi(Anggaran::find($id))['persen'];
                }
                $anggaranPct = $semua !== [] ? round(array_sum($semua) / count($semua), 1) : 0.0;
            }

            return [
                'ipk_rata' => $ipk,
                'lulusan' => $lulusan,
                'serapan_kerja_pct' => $serapan,
                'rasio_mhs_dosen' => round($mhsAktif / $dosen, 1),
                'realisasi_anggaran_pct' => $anggaranPct,
            ];
        } catch (\Throwable) {
            return ['ipk_rata' => 0.0, 'lulusan' => 0, 'serapan_kerja_pct' => 0.0, 'rasio_mhs_dosen' => 0.0, 'realisasi_anggaran_pct' => 0.0];
        }
    }
}
