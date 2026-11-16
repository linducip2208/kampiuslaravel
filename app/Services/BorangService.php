<?php

namespace App\Services;

use App\Models\AkreditasiDokumen;
use App\Models\Cpmk;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\TracerAlumni;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ide bank: draf borang akreditasi dari data internal
 * (rekap OBE + tracer + dokumen). Ekspor, BUKAN klaim akreditasi —
 * angka harus diverifikasi asesor manual.
 */
class BorangService
{
    /**
     * @return array{profil: array<string, int|string>, cpmk: list<array{kode: string, mk: string, asesmen: int}>, tracer: list<array{tahun: mixed, responden: int, terserap: int}>, dokumen: list<array{kategori: string, total: int, terverifikasi: int}>}
     */
    public static function ringkasan(int $prodiId): array
    {
        $prodi = Prodi::find($prodiId);

        $profil = ['prodi' => (string) ($prodi->nama ?? '-'), 'aktif' => 0, 'lulus' => 0, 'cuti' => 0];

        try {
            if (Schema::hasTable('mahasiswa')) {
                $profil['aktif'] = (int) Mahasiswa::where('prodi_id', $prodiId)->where('status', 'aktif')->count();
                $profil['lulus'] = (int) Mahasiswa::where('prodi_id', $prodiId)->where('status', 'lulus')->count();
                $profil['cuti'] = (int) Mahasiswa::where('prodi_id', $prodiId)->where('status', 'cuti')->count();
            }
        } catch (\Throwable) {
        }

        $cpmk = [];

        try {
            if (Schema::hasTable('cpmk') && Schema::hasTable('matakuliah')) {
                $mkIds = DB::table('matakuliah')->where('prodi_id', $prodiId)->pluck('id', 'kode');
                $byMk = DB::table('cpmk')->selectRaw('matakuliah_id, COUNT(*) as jml')->whereIn('matakuliah_id', $mkIds->values())->groupBy('matakuliah_id')->pluck('jml', 'matakuliah_id');
                $ases = Schema::hasTable('cpmk_asesmen')
                    ? DB::table('cpmk_asesmen')->selectRaw('cpmk_id, COUNT(*) as jml')->groupBy('cpmk_id')->pluck('jml', 'cpmk_id')
                    : collect();

                foreach (Cpmk::with('matakuliah')->whereIn('matakuliah_id', $mkIds->values())->orderBy('id')->get() as $c) {
                    $cpmk[] = [
                        'kode' => (string) $c->kode,
                        'mk' => (string) ($c->matakuliah->kode ?? '-'),
                        'asesmen' => (int) ($ases[$c->id] ?? 0),
                    ];
                }
            }
        } catch (\Throwable) {
            $cpmk = [];
        }

        $tracer = [];

        try {
            if (Schema::hasTable('tracer_alumni')) {
                $tracer = TracerAlumni::whereHas('mahasiswa', fn ($q) => $q->where('prodi_id', $prodiId))
                    ->selectRaw("tahun_lulus, COUNT(*) as responden, SUM(CASE WHEN status_kerja IN ('bekerja','wirausaha') THEN 1 ELSE 0 END) as terserap")
                    ->groupBy('tahun_lulus')
                    ->orderBy('tahun_lulus')
                    ->get()
                    ->map(fn ($r) => ['tahun' => $r->tahun_lulus, 'responden' => (int) $r->responden, 'terserap' => (int) $r->terserap])
                    ->all();
            }
        } catch (\Throwable) {
            $tracer = [];
        }

        $dokumen = [];

        try {
            if (Schema::hasTable('akreditasi_dokumen')) {
                $dokumen = AkreditasiDokumen::where('prodi_id', $prodiId)
                    ->selectRaw('kategori, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as terverifikasi', ['terverifikasi'])
                    ->groupBy('kategori')
                    ->orderBy('kategori')
                    ->get()
                    ->map(fn ($r) => ['kategori' => (string) $r->kategori, 'total' => (int) $r->total, 'terverifikasi' => (int) $r->terverifikasi])
                    ->all();
            }
        } catch (\Throwable) {
            $dokumen = [];
        }

        return compact('profil', 'cpmk', 'tracer', 'dokumen');
    }
}
