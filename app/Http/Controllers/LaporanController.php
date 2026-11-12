<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(): View
    {
        return view('laporan.index');
    }

    public function templateImpor(string $jenis): StreamedResponse
    {
        abort_unless(in_array($jenis, ['mahasiswa', 'matakuliah'], true), 404);

        $header = $jenis === 'matakuliah'
            ? ['kode', 'nama', 'prodi_kode', 'sks', 'semester']
            : ['nim', 'nama', 'email', 'hp', 'prodi_kode', 'angkatan'];

        return response()->streamDownload(function () use ($header) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header, ';');
            fclose($out);
        }, "template-{$jenis}.csv");
    }

    public function exportTagihan(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nim', 'nama', 'jenis', 'nominal', 'terbayar', 'diskon', 'status'], ';');

            Tagihan::with(['mahasiswa.user'])->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $t) {
                    fputcsv($out, [
                        $t->mahasiswa->nim ?? '',
                        $t->mahasiswa->user->name ?? '',
                        $t->jenis,
                        $t->nominal,
                        $t->terbayar,
                        $t->diskon,
                        $t->status,
                    ], ';');
                }
            });

            fclose($out);
        }, 'rekap-tagihan-'.now()->format('Ymd-His').'.csv');
    }

    public function exportAbsensi(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nim', 'pertemuan', 'tanggal', 'status'], ';');

            Absensi::with(['mahasiswa', 'pertemuan'])->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $a) {
                    fputcsv($out, [
                        $a->mahasiswa->nim ?? '',
                        'Pertemuan '.$a->pertemuan->ke ?? '',
                        $a->pertemuan->tanggal ?? '',
                        $a->status,
                    ], ';');
                }
            });

            fclose($out);
        }, 'rekap-absensi-'.now()->format('Ymd-His').'.csv');
    }

    public function rekapAbsensi(): array
    {
        $total = (int) DB::table('absensi')->count();

        return [
            'total' => $total,
            'hadir_pct' => $total > 0 ? round(DB::table('absensi')->where('status', 'hadir')->count() / $total * 100, 1) : 0,
            'di_bawah_75' => DB::table('absensi')
                ->selectRaw('mahasiswa_id, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hadir', ['hadir'])
                ->groupBy('mahasiswa_id')
                ->get()
                ->filter(fn ($r) => $r->total > 0 && ($r->hadir / $r->total * 100) < 75)
                ->count(),
        ];
    }
}
