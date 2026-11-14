<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\KalenderEvent;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Pembayaran;
use App\Models\Pengumuman;
use App\Models\Pertemuan;
use App\Models\PmbPendaftar;
use App\Models\Prodi;
use App\Models\Sidang;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Services\KalenderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(): View
    {
        $has = fn (string $table): bool => Schema::hasTable($table);

        // --- Statistik utama (real queries, guarded) ---
        $mahasiswaAktif = $has('mahasiswa') ? Mahasiswa::where('status', 'aktif')->count() : 0;
        $mahasiswaBaru = $has('mahasiswa')
            ? Mahasiswa::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()
            : 0;
        $dosenAktif = $has('dosen') ? Dosen::where('status', 'aktif')->count() : 0;
        $prodiCount = $has('prodi') ? Prodi::count() : 0;
        $krsPending = $has('krs') ? Krs::where('status', 'diajukan')->count() : 0;
        $pmbPending = $has('pmb_pendaftars') ? PmbPendaftar::where('status', 'menunggu')->count() : 0;
        $verifikasiPending = $has('pembayaran') ? Pembayaran::where('status', 'pending')->count() : 0;

        $outstanding = 0;
        if ($has('tagihan')) {
            $outstanding = (int) Tagihan::whereIn('status', ['belum_bayar', 'sebagian'])
                ->selectRaw('COALESCE(SUM(nominal + COALESCE(denda,0) - COALESCE(terbayar,0)),0) as total')
                ->value('total');
        }

        $pendapatanBulanIni = 0;
        $pendapatanTotal = 0;
        if ($has('pembayaran')) {
            $pendapatanBulanIni = (int) Pembayaran::where('status', 'verified')
                ->whereYear('verified_at', now()->year)->whereMonth('verified_at', now()->month)
                ->sum('nominal');
            $pendapatanTotal = (int) Pembayaran::where('status', 'verified')->sum('nominal');
        }

        // --- Earnings chart: 6 bulan terakhir, pemasukan (verified) vs tagihan terbit ---
        $pembayaranPerBulan = [];
        $tagihanPerBulan = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labels[] = $bulan->translatedFormat('M');
            $pembayaranPerBulan[] = $has('pembayaran') ? (int) Pembayaran::where('status', 'verified')
                ->whereYear('verified_at', $bulan->year)
                ->whereMonth('verified_at', $bulan->month)
                ->sum('nominal') : 0;
            $tagihanPerBulan[] = $has('tagihan') ? (int) Tagihan::whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->sum('nominal') : 0;
        }

        // --- Academic Performance: approval rate KRS per 8 tahun akademik terakhir ---
        $perfLabels = [];
        $perfValues = [];
        if ($has('krs') && $has('tahun_akademik')) {
            $taList = TahunAkademik::orderBy('tahun')->orderBy('semester')->take(8)->get();
            // Fallback: ambil 8 terakhir bila data banyak
            if ($taList->count() > 8) {
                $taList = TahunAkademik::orderByDesc('tahun')->orderByDesc('semester')->take(8)->get()->reverse()->values();
            }
            $taIds = $taList->pluck('id')->all();
            $rekap = $taIds !== []
                ? Krs::selectRaw('tahun_akademik_id, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as ok', [Krs::STATUS_DISETUJUI])
                    ->whereIn('tahun_akademik_id', $taIds)
                    ->groupBy('tahun_akademik_id')
                    ->get()->keyBy('tahun_akademik_id')
                : collect();
            foreach ($taList as $ta) {
                $total = (int) ($rekap[$ta->id]->total ?? 0);
                $ok = (int) ($rekap[$ta->id]->ok ?? 0);
                $perfLabels[] = ($ta->semester === 'genap' ? 'Genap' : 'Ganjil').' '.substr((string) $ta->tahun, 0, 4);
                $perfValues[] = $total > 0 ? (int) round($ok / $total * 100) : 0;
            }
        }

        // --- Donut mahasiswa per angkatan (top 4) ---
        $angkatanBreakdown = [];
        $totalMahasiswa = $has('mahasiswa') ? Mahasiswa::count() : 0;
        if ($has('mahasiswa')) {
            $angkatanBreakdown = Mahasiswa::selectRaw('angkatan, COUNT(*) as total')
                ->groupBy('angkatan')->orderByDesc('total')->take(4)->pluck('total', 'angkatan')->all();
        }

        // --- Distribusi status mahasiswa ---
        $statusBreakdown = $has('mahasiswa')
            ? Mahasiswa::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all()
            : [];

        $krsStatus = $has('krs')
            ? Krs::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();

        // --- Pesan / pengumuman terbaru (real) ---
        $messages = $has('pengumuman')
            ? Pengumuman::latest('published_at')->take(4)->get()
            : collect();

        // --- Aktivitas mahasiswa: KRS terbaru + sidang terbaru + pengumuman ---
        $aktivitasMahasiswa = collect();
        if ($has('krs')) {
            $aktivitasMahasiswa = $aktivitasMahasiswa->merge(
                Krs::with(['mahasiswa.user'])->latest('id')->take(3)->get()
                    ->map(fn ($k) => [
                        'icon' => 'krs',
                        'judul' => 'KRS '.($k->mahasiswa->user->name ?? '#'.$k->mahasiswa_id),
                        'sub' => 'Status: '.$k->status.' • '.($k->updated_at?->diffForHumans() ?? ''),
                    ])
            );
        }

        $agenda = collect();
        if ($has('pertemuan')) {
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
        if ($has('kalender_events')) {
            $agenda = $agenda->merge(
                KalenderEvent::whereDate('tgl_mulai', '>=', today())
                    ->orderBy('tgl_mulai')->take(5)->get()
                    ->map(fn ($e) => ['tanggal' => $e->tgl_mulai->format('d M'), 'judul' => $e->judul, 'sub' => $e->jenis])
            );
        }
        $agenda = $agenda->take(8);

        $aktivitas = Schema::hasTable('audit_logs')
            ? AuditLog::with('user')->latest('id')->take(6)->get()
            : collect();

        // --- Scope pribadi mahasiswa: jangan tampilkan angka global sebagai miliknya ---
        $saya = ['outstanding' => 0, 'tagihan_aktif' => 0, 'krs_status' => null, 'krs_pending' => null];
        if ($has('mahasiswa')) {
            $user = auth()->user();
            $isMhs = $user && $user->hasRole('mahasiswa') && ! $user->hasAnyRole(['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'pimpinan']);
            if ($isMhs && $has('tagihan')) {
                $mhsId = Mahasiswa::where('user_id', $user->id)->value('id');
                if ($mhsId) {
                    $saya['outstanding'] = (int) Tagihan::where('mahasiswa_id', $mhsId)
                        ->whereIn('status', ['belum_bayar', 'sebagian'])
                        ->selectRaw('COALESCE(SUM(nominal + COALESCE(denda,0) - COALESCE(terbayar,0)),0) as total')
                        ->value('total');
                    $saya['tagihan_aktif'] = Tagihan::where('mahasiswa_id', $mhsId)
                        ->whereIn('status', ['belum_bayar', 'sebagian'])->count();
                    if ($has('krs')) {
                        $saya['krs_status'] = Krs::where('mahasiswa_id', $mhsId)
                            ->latest('id')->value('status');
                    }
                }
            }
        }

        // --- Scope dosen: KRS menunggu khusus bimbingannya (selaras PersetujuanPa) ---
        // $bimbinganIds null = tanpa scope (staf melihat semua); array = hanya milik bimbingan.
        $bimbinganIds = null;
        if ($has('krs') && $has('mahasiswa')) {
            $user = auth()->user();
            $isDosenOnly = $user && $user->hasRole('dosen') && ! $user->hasAnyRole(['mahasiswa', 'superadmin', 'admin_prodi']);
            if ($isDosenOnly && Schema::hasTable('dosen')) {
                try {
                    $dosenId = DB::table('dosen')->where('user_id', $user->id)->value('id');
                    if ($dosenId && Schema::hasColumn('mahasiswa', 'dosen_pa_id')) {
                        $bimbinganIds = DB::table('mahasiswa')->where('dosen_pa_id', $dosenId)->pluck('id')->all();
                        $saya['krs_pending'] = Krs::where('status', Krs::STATUS_DIAJUKAN)
                            ->whereIn('mahasiswa_id', $bimbinganIds ?: [0])->count();
                    }
                } catch (Throwable) {
                    // abaikan, fallback ke angka global
                }
            }
        }

        // --- Antrean operasional (guarded, dibatasi) ---
        $funnel = $has('pmb_pendaftars')
            ? PmbPendaftar::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all()
            : [];
        $tunggakanTop = ($has('tagihan') && $has('mahasiswa'))
            ? Tagihan::with(['mahasiswa.user'])->whereIn('status', ['belum_bayar', 'sebagian'])
                ->orderByRaw('(nominal - terbayar) DESC')->take(5)->get()
            : collect();
        $krsAntreanQuery = $has('krs')
            ? Krs::with(['mahasiswa.user'])->where('status', 'diajukan')
            : null;
        if ($krsAntreanQuery && $bimbinganIds !== null) {
            $krsAntreanQuery->whereIn('mahasiswa_id', $bimbinganIds ?: [0]);
        }
        $krsAntrean = $krsAntreanQuery ? $krsAntreanQuery->latest('id')->take(5)->get() : collect();

        return view('dashboard', [
            'stats' => [
                'mahasiswa' => $totalMahasiswa,
                'mahasiswa_aktif' => $mahasiswaAktif,
                'mahasiswa_baru' => $mahasiswaBaru,
                'dosen' => Dosen::count(),
                'dosen_aktif' => $dosenAktif,
                'prodi' => $prodiCount,
                'matakuliah' => $has('matakuliah') ? Matakuliah::count() : 0,
                'krs_pending' => $krsPending,
                'pmb_pending' => $pmbPending,
                'verifikasi_pending' => $verifikasiPending,
                'outstanding' => $outstanding,
                'pendapatan_bulan_ini' => $pendapatanBulanIni,
                'pendapatan_total' => $pendapatanTotal,
            ],
            'chartLabels' => $labels,
            'chartNominal' => $pembayaranPerBulan,
            'chartTagihan' => $tagihanPerBulan,
            'perfLabels' => $perfLabels,
            'perfValues' => $perfValues,
            'angkatanBreakdown' => $angkatanBreakdown,
            'statusBreakdown' => $statusBreakdown,
            'messages' => $messages,
            'aktivitasMahasiswa' => $aktivitasMahasiswa,
            'krsStatus' => $krsStatus,
            'agenda' => $agenda,
            'locks' => KalenderService::locks(),
            'aktivitas' => $aktivitas,
            'saya' => $saya,
            'funnel' => $funnel,
            'tunggakanTop' => $tunggakanTop,
            'krsAntrean' => $krsAntrean,
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

        $stats['pendapatan_verified'] = Schema::hasTable('pembayaran')
            ? (int) DB::table('pembayaran')->where('status', 'verified')->sum('nominal')
            : 0;

        $stats['piutang'] = Schema::hasTable('tagihan')
            ? (int) DB::table('tagihan')->whereIn('status', ['belum_bayar', 'sebagian'])->selectRaw('COALESCE(SUM(nominal - terbayar),0) as t')->value('t')
            : 0;

        $stats['krs_pending'] = Schema::hasTable('krs') ? DB::table('krs')->where('status', 'diajukan')->count() : 0;
        $stats['krs_disetujui_hari_ini'] = Schema::hasTable('krs')
            ? DB::table('krs')->where('status', 'disetujui')->whereDate('updated_at', today())->count()
            : 0;
        $stats['pmb_menunggu'] = Schema::hasTable('pmb_pendaftars') ? DB::table('pmb_pendaftars')->where('status', 'menunggu')->count() : 0;

        $tren = [];
        if (Schema::hasTable('pembayaran')) {
            for ($i = 5; $i >= 0; $i--) {
                $b = now()->subMonths($i);
                $tren[] = [
                    'label' => $b->translatedFormat('M'),
                    'nominal' => (int) DB::table('pembayaran')->where('status', 'verified')->whereYear('verified_at', $b->year)->whereMonth('verified_at', $b->month)->sum('nominal'),
                ];
            }
        }

        return view('dashboard.pimpinan', ['stats' => $stats, 'tren' => $tren]);
    }
}
