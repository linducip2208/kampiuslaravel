<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Pembayaran;
use App\Models\Pertemuan;
use App\Models\Prodi;
use App\Models\Sidang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $pembayaranPerBulan = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labels[] = $bulan->translatedFormat('M');
            $pembayaranPerBulan[] = (int) Pembayaran::where('status', 'verified')
                ->whereYear('verified_at', $bulan->year)
                ->whereMonth('verified_at', $bulan->month)
                ->sum('nominal');
        }

        $krsStatus = Krs::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $agenda = collect();
        if (Schema::hasTable('pertemuan')) {
            $agenda = $agenda->merge(
                Pertemuan::with('kelas.matakuliah')->whereDate('tanggal', '>=', today())
                    ->orderBy('tanggal')->take(5)->get()
                    ->map(fn ($p) => ['tanggal' => $p->tanggal->format('d M'), 'judul' => 'Kuliah: '.($p->kelas->matakuliah->nama ?? 'Kelas #'.$p->kelas_id), 'sub' => 'Pertemuan ke-'.$p->ke])
            );
        }
        if (Schema::hasTable('sidang')) {
            $agenda = $agenda->merge(
                Sidang::whereDate('tanggal', '>=', today())
                    ->orderBy('tanggal')->take(5)->get()
                    ->map(fn ($s) => ['tanggal' => Carbon::parse($s->tanggal)->format('d M'), 'judul' => 'Sidang '.($s->jenis ?? ''), 'sub' => $s->status])
            );
        }
        $agenda = $agenda->take(6);

        $aktivitas = Schema::hasTable('audit_logs')
            ? AuditLog::with('user')->latest('id')->take(6)->get()
            : collect();

        return view('dashboard', [
            'stats' => [
                'mahasiswa' => Mahasiswa::count(),
                'dosen' => Dosen::count(),
                'prodi' => Prodi::count(),
                'matakuliah' => Matakuliah::count(),
            ],
            'chartLabels' => $labels,
            'chartNominal' => $pembayaranPerBulan,
            'krsStatus' => $krsStatus,
            'agenda' => $agenda,
            'aktivitas' => $aktivitas,
        ]);
    }

    public function pimpinan(): View
    {
        $stats = [];
        foreach (['mahasiswa' => 'mahasiswa', 'dosen' => 'dosen', 'skripsi_judul' => 'skripsi_judul', 'sidang' => 'sidang', 'tagihan' => 'tagihan', 'pembayaran' => 'pembayaran', 'surat' => 'surat', 'pengumuman' => 'pengumuman'] as $key => $table) {
            $stats[$key] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        $stats['tagihan_belum_bayar'] = Schema::hasTable('tagihan')
            ? DB::table('tagihan')->whereIn('status', ['belum_bayar', 'sebagian'])->count()
            : 0;

        $stats['pembayaran_pending'] = Schema::hasTable('pembayaran')
            ? DB::table('pembayaran')->where('status', 'pending')->count()
            : 0;

        return view('dashboard.pimpinan', ['stats' => $stats]);
    }
}
