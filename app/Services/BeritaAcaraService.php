<?php

namespace App\Services;

use App\Models\Sidang;
use Illuminate\Support\Facades\Storage;

class BeritaAcaraService
{
    public static function hurufMutu(?float $nilai): string
    {
        if ($nilai === null) {
            return '-';
        }
        if ($nilai >= 85) {
            return 'A';
        }
        if ($nilai >= 70) {
            return 'B';
        }
        if ($nilai >= 55) {
            return 'C';
        }
        if ($nilai >= 40) {
            return 'D';
        }

        return 'E';
    }

    public static function generate(Sidang $sidang): string
    {
        $sidang->loadMissing(['judul.mahasiswa.user', 'judul.mahasiswa.prodi', 'ruang', 'pengujis.dosen.user']);

        $nilai = $sidang->nilai !== null ? (float) $sidang->nilai : null;
        if ($nilai === null) {
            $rata = $sidang->pengujis()->avg('nilai');
            $nilai = $rata !== null ? round((float) $rata, 2) : null;
            if ($nilai !== null) {
                $sidang->update(['nilai' => $nilai]);
            }
        }

        $mhs = $sidang->judul?->mahasiswa;
        $barisPenguji = $sidang->pengujis->map(
            fn ($p) => '- '.($p->dosen?->user?->name ?? 'Dosen #'.$p->dosen_id)." ({$p->peran}): ".($p->nilai ?? '-')
        )->implode("\n");

        $isi = "BERITA ACARA SIDANG {$sidang->jenis}\n"
            .'Tanggal: '.($sidang->tanggal ?? '-')."\n"
            .'Mahasiswa: '.($mhs?->user?->name ?? '-').' ('.($mhs?->nim ?? '-').")\n"
            .'Judul: '.($sidang->judul?->judul ?? '-')."\n"
            .'Ruang: '.($sidang->ruang?->nama ?? '-')."\n"
            ."Penguji:\n{$barisPenguji}\n"
            .'Nilai akhir: '.($nilai ?? '-').' ('.self::hurufMutu($nilai).")\n"
            .'Status: '.($sidang->status ?? '-')."\n";

        $path = 'ba/sidang-'.$sidang->id.'-'.now()->format('YmdHis').'.txt';
        Storage::disk('local')->put($path, $isi);

        $sidang->update(['berita_acara_path' => $path]);

        return $path;
    }
}
