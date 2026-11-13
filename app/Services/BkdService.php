<?php

namespace App\Services;

use App\Models\BebanDosen;
use App\Models\Kelas;
use App\Models\SkMengajar;
use Illuminate\Support\Facades\DB;

class BkdService
{
    public static function totalSks(int $dosenId, ?int $tahunId = null): int
    {
        return (int) BebanDosen::where('dosen_id', $dosenId)
            ->when($tahunId, fn ($q) => $q->where('tahun_akademik_id', $tahunId))
            ->where('status', 'disetujui')
            ->sum('sks');
    }

    public static function rekapKategori(int $dosenId, ?int $tahunId = null): array
    {
        return BebanDosen::where('dosen_id', $dosenId)
            ->when($tahunId, fn ($q) => $q->where('tahun_akademik_id', $tahunId))
            ->where('status', 'disetujui')
            ->selectRaw('kategori, SUM(sks) as total_sks, COUNT(*) as total_kegiatan')
            ->groupBy('kategori')
            ->pluck('total_sks', 'kategori')
            ->all();
    }

    public static function sinkronMengajarDariKelas(int $dosenId, int $tahunId): int
    {
        $kelas = Kelas::with('matakuliah')->where('dosen_id', $dosenId)->where('tahun_akademik_id', $tahunId)->get();

        $dibuat = 0;
        foreach ($kelas as $k) {
            $uraian = 'Mengajar '.($k->matakuliah->nama ?? $k->nama).' ('.$k->nama.')';
            $exists = BebanDosen::where('dosen_id', $dosenId)
                ->where('tahun_akademik_id', $tahunId)
                ->where('kategori', 'pendidikan')
                ->where('uraian', $uraian)
                ->exists();
            if ($exists) {
                continue;
            }
            BebanDosen::create([
                'dosen_id' => $dosenId,
                'tahun_akademik_id' => $tahunId,
                'kategori' => 'pendidikan',
                'uraian' => $uraian,
                'sks' => (int) ($k->matakuliah->sks ?? 2),
                'status' => 'disetujui',
            ]);
            $dibuat++;
        }

        return $dibuat;
    }

    public static function generateSk(int $dosenId, int $tahunId): SkMengajar
    {
        return DB::transaction(function () use ($dosenId, $tahunId) {
            $existing = SkMengajar::where('dosen_id', $dosenId)->where('tahun_akademik_id', $tahunId)->first();
            if ($existing) {
                return $existing;
            }

            $kelas = Kelas::with('matakuliah')->where('dosen_id', $dosenId)->where('tahun_akademik_id', $tahunId)->get();
            $totalSks = (int) $kelas->sum(fn ($k) => (int) ($k->matakuliah->sks ?? 2));
            $daftar = $kelas->map(fn ($k) => ($k->matakuliah->kode ?? '-').' '.($k->matakuliah->nama ?? $k->nama).' ['.$k->nama.']')->implode('; ');

            $year = (string) date('Y');
            $seq = SkMengajar::whereYear('created_at', $year)->lockForUpdate()->count() + 1;
            $nomor = 'SK/MENGAJAR/'.$year.'/'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            return SkMengajar::create([
                'dosen_id' => $dosenId,
                'tahun_akademik_id' => $tahunId,
                'nomor' => $nomor,
                'tanggal_terbit' => now()->toDateString(),
                'total_sks' => $totalSks,
                'daftar_kelas' => $daftar ?: null,
            ]);
        });
    }
}
