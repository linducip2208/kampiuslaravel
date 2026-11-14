<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JurnalKeuangan;
use App\Models\Krs;
use App\Models\Nilai;
use App\Models\PmbPendaftar;
use App\Models\Tagihan;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller implements HasMiddleware
{
    /**
     * M-037: otorisasi ekspor di level controller (defense-in-depth di atas
     * middleware role grup rute). Chunked streaming tetap (export* pakai
     * chunk(500) + streamDownload).
     */
    public static function middleware(): array
    {
        return ['can:laporan.view'];
    }

    public function index(): View
    {
        return view('laporan.index');
    }

    public function templateImpor(string $jenis): StreamedResponse
    {
        $map = [
            'mahasiswa' => ['nim', 'nama', 'email', 'hp', 'prodi_kode', 'angkatan'],
            'matakuliah' => ['kode', 'nama', 'prodi_kode', 'sks', 'semester'],
            'dosen' => ['nidn', 'nama', 'email', 'prodi_kode'],
            'krs' => ['nim', 'tahun', 'semester', 'kelas_id'],
            'nilai' => ['nim', 'kelas_id', 'tugas', 'uts', 'uas', 'kehadiran'],
            'tagihan' => ['nim', 'tahun', 'semester', 'jenis', 'nominal'],
        ];

        abort_unless(isset($map[$jenis]), 404);

        $header = $map[$jenis];

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

    public function exportTunggakan(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nim', 'nama', 'jenis', 'tahun', 'semester', 'nominal', 'terbayar', 'sisa', 'diskon', 'status'], ';');

            Tagihan::with(['mahasiswa.user', 'tahunAkademik'])
                ->whereIn('status', ['belum_bayar', 'sebagian'])
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $t) {
                        fputcsv($out, [
                            $t->mahasiswa->nim ?? '',
                            $t->mahasiswa->user->name ?? '',
                            $t->jenis,
                            $t->tahunAkademik->tahun ?? '',
                            $t->tahunAkademik->semester ?? '',
                            $t->nominal,
                            $t->terbayar,
                            max(0, (int) $t->nominal - (int) $t->terbayar),
                            $t->diskon,
                            $t->status,
                        ], ';');
                    }
                });

            fclose($out);
        }, 'tunggakan-'.now()->format('Ymd-His').'.csv');
    }

    public function exportPmb(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['no_daftar', 'nama', 'email', 'hp', 'sekolah_asal', 'prodi1', 'prodi2', 'status'], ';');

            PmbPendaftar::with(['prodi1', 'prodi2'])->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    fputcsv($out, [
                        $p->no_daftar,
                        $p->nama,
                        $p->email,
                        $p->hp,
                        $p->sekolah_asal,
                        $p->prodi1->kode ?? '',
                        $p->prodi2->kode ?? '',
                        $p->status,
                    ], ';');
                }
            });

            fclose($out);
        }, 'pmb-'.now()->format('Ymd-His').'.csv');
    }

    public function exportKrs(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nim', 'nama', 'tahun', 'semester', 'status', 'total_sks', 'jumlah_mk'], ';');

            Krs::with(['mahasiswa.user', 'tahunAkademik'])->withCount('details')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $k) {
                    fputcsv($out, [
                        $k->mahasiswa->nim ?? '',
                        $k->mahasiswa->user->name ?? '',
                        $k->tahunAkademik->tahun ?? '',
                        $k->tahunAkademik->semester ?? '',
                        $k->status,
                        $k->total_sks,
                        $k->details_count,
                    ], ';');
                }
            });

            fclose($out);
        }, 'krs-'.now()->format('Ymd-His').'.csv');
    }

    public function exportNilai(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nim', 'nama', 'kelas_id', 'matakuliah', 'tugas', 'uts', 'uas', 'kehadiran', 'na', 'huruf'], ';');

            Nilai::with(['mahasiswa.user', 'kelas.matakuliah'])->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $n) {
                    fputcsv($out, [
                        $n->mahasiswa->nim ?? '',
                        $n->mahasiswa->user->name ?? '',
                        $n->kelas_id,
                        $n->kelas->matakuliah->kode ?? $n->kelas->nama ?? '',
                        $n->tugas,
                        $n->uts,
                        $n->uas,
                        $n->kehadiran,
                        $n->na,
                        $n->huruf,
                    ], ';');
                }
            });

            fclose($out);
        }, 'nilai-'.now()->format('Ymd-His').'.csv');
    }

    /**
     * Neraca saldo dari jurnal yang sudah diposting (bukan dokumen resmi akuntansi).
     * Arsip yang di-soft-delete otomatis dikecualikan.
     */
    public function exportNeraca(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['akun', 'debet', 'kredit', 'saldo'], ';');

            $debet = JurnalKeuangan::selectRaw('akun_debet as akun, SUM(nominal) as total')
                ->groupBy('akun_debet')->pluck('total', 'akun');
            $kredit = JurnalKeuangan::selectRaw('akun_kredit as akun, SUM(nominal) as total')
                ->groupBy('akun_kredit')->pluck('total', 'akun');

            foreach ($debet->keys()->merge($kredit->keys())->unique()->sort()->values() as $akun) {
                $d = (int) ($debet[$akun] ?? 0);
                $k = (int) ($kredit[$akun] ?? 0);
                fputcsv($out, [$akun, $d, $k, $d - $k], ';');
            }

            fclose($out);
        }, 'neraca-saldo-'.now()->format('Ymd-His').'.csv');
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
