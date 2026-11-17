<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\BayarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\KtmController;
use App\Http\Controllers\KwitansiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PddiktiController;
use App\Http\Controllers\PencarianController;
use App\Http\Controllers\PmbController;
use App\Http\Controllers\PmbFunnelController;
use App\Http\Controllers\PublikController;
use App\Http\Controllers\SessionAuthController;
use App\Http\Controllers\SkMengajarController;
use App\Http\Controllers\SlipController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\TranskripController;
use App\Models\Absensi;
use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\MagangLowongan;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Nilai;
use App\Models\Pembayaran;
use App\Models\Pengumuman;
use App\Models\Pertemuan;
use App\Models\PmbPendaftar;
use App\Models\Prodi;
use App\Models\Tagihan;
use App\Models\Tugas;
use App\Services\BorangService;
use App\Services\KeuanganService;
use App\Services\PmbArsipService;
use App\Services\ScormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Health check publik (load balancer / monitoring).
Route::get('/health', function () {
    try {
        DB::select('select 1');

        // Deteksi migrasi yang sudah di-push tapi belum jalan (contoh insiden deleted_at).
        $pending = 0;
        try {
            $ran = app('migrator')->getRepository()->getRan();
            $files = collect(glob(database_path('migrations/*.php')) ?: [])
                ->map(fn ($f) => basename($f, '.php'))->all();
            $pending = count(array_diff($files, $ran));
        } catch (Throwable) {
            $pending = -1;
        }

        // Antrean: ukuran jobs + yang gagal (tabel ada bila driver database dipakai).
        $jobs = 0;
        $failed = 0;
        try {
            $jobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        } catch (Throwable) {
            // Metrik antrean tidak boleh menggagalkan health check.
        }

        return response()->json(['ok' => $pending === 0, 'db' => 'up', 'pending_migrations' => $pending, 'queue' => $jobs, 'queue_failed' => $failed, 'time' => now()->toIso8601String(), 'checks' => ['database' => 'up', 'migrations' => $pending === 0 ? 'ok' : $pending.' pending', 'queue' => $failed === 0 ? "ok ({$jobs} antre)" : "{$failed} gagal, {$jobs} antre"]], $pending === 0 ? 200 : 503);
    } catch (Throwable $e) {
        return response()->json(['ok' => false, 'db' => 'down'], 503);
    }
})->name('health');

// Publik (tanpa auth) — pSEO
Route::get('/', function () {
    $safe = function (string $table, callable $query) {
        try {
            return Schema::hasTable($table) ? $query() : 0;
        } catch (Throwable) {
            return 0;
        }
    };

    $stats = [
        'mahasiswa' => $safe('mahasiswa', fn () => Mahasiswa::count()),
        'prodi' => $safe('prodi', fn () => Prodi::count()),
        'matakuliah' => $safe('matakuliah', fn () => Matakuliah::count()),
        'audit' => $safe('audit_logs', fn () => AuditLog::count()),
        'piutang' => $safe('tagihan', fn () => Tagihan::whereIn('status', ['belum_bayar', 'sebagian'])->sum(DB::raw('nominal + COALESCE(denda,0) - COALESCE(terbayar,0)'))),
        'krs_pending' => $safe('krs', fn () => Krs::where('status', 'diajukan')->count()),
        'krs_ok' => $safe('krs', fn () => Krs::where('status', 'disetujui')->whereDate('updated_at', today())->count()),
        'dosen' => $safe('dosen', fn () => Dosen::where('status', 'aktif')->count()),
        'krs_pct' => $safe('krs', function () {
            $total = Krs::count();

            return $total > 0 ? (int) round(Krs::where('status', 'disetujui')->count() / $total * 100) : 0;
        }),
        'pmb_baru' => $safe('pmb_pendaftars', fn () => PmbPendaftar::whereMonth('created_at', now()->month)->count()),
        'hadir_pct' => $safe('absensi', function () {
            $total = Absensi::count();

            return $total > 0 ? (int) round(Absensi::where('status', 'hadir')->count() / $total * 100) : 0;
        }),
        'absensi_breakdown' => (function () use ($safe) {
            $b = $safe('absensi', fn () => Absensi::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all());

            return is_array($b) ? $b : [];
        })(),
        'grades' => (function () use ($safe) {
            $g = $safe('nilai', fn () => Nilai::selectRaw('huruf, COUNT(*) as total')->groupBy('huruf')->pluck('total', 'huruf')->all());

            return is_array($g) ? $g : [];
        })(),
        'top_mahasiswa' => (function () use ($safe) {
            $m = $safe('mahasiswa', fn () => Mahasiswa::with(['prodi', 'user'])->where('status', 'aktif')->take(3)->get());

            return is_iterable($m) ? $m : [];
        })(),
        'notifikasi' => (function () use ($safe) {
            $n = $safe('pengumuman', fn () => Pengumuman::whereNotNull('published_at')->latest('published_at')->take(3)->get());

            return is_iterable($n) ? $n : [];
        })(),
        'lunas_pct' => $safe('tagihan', function () {
            $total = (float) Tagihan::sum(DB::raw('nominal + COALESCE(denda,0)'));
            $bayar = (float) Tagihan::sum('terbayar');

            return $total > 0 ? (int) round($bayar / $total * 100) : 0;
        }),
        'bayar_bulanan' => (function () use ($safe) {
            $out = ['labels' => [], 'nominal' => []];
            for ($i = 5; $i >= 0; $i--) {
                $b = now()->subMonths($i);
                $out['labels'][] = $b->translatedFormat('M');
                $out['nominal'][] = (int) $safe('pembayaran', fn () => Pembayaran::where('status', 'verified')->whereYear('verified_at', $b->year)->whereMonth('verified_at', $b->month)->sum('nominal'));
            }

            return $out;
        })(),
    ];

    return view('welcome', compact('stats'));
})->name('home');

Route::get('/login', [SessionAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [SessionAuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

// M-065: SSO Google/Microsoft (OAuth2 manual, tanpa Socialite).
// Aktif bila client_id/secret terisi; tanpa itu redirect kembali ke login.
Route::middleware('throttle:login')->group(function (): void {
    Route::get('/auth/{provider}/redirect', [SsoController::class, 'redirect'])->whereIn('provider', ['google', 'microsoft'])->name('sso.redirect');
    Route::get('/auth/{provider}/callback', [SsoController::class, 'callback'])->whereIn('provider', ['google', 'microsoft'])->name('sso.callback');
});

Route::get('/prodi', [PublikController::class, 'prodi'])->name('publik.prodi');
Route::get('/prodi/{slug}', [PublikController::class, 'prodiDetail'])->name('publik.prodi.detail');
Route::get('/pengumuman', [PublikController::class, 'pengumuman'])->name('publik.pengumuman');
Route::get('/pengumuman/{id}', [PublikController::class, 'pengumumanDetail'])->name('publik.pengumuman.detail');
Route::get('/dokumentasi', [PublikController::class, 'dokumen'])->name('dokumen.index');
Route::get('/dokumentasi/{file}', [PublikController::class, 'dokumen'])->name('dokumen.show');

// Portal publik: alumni + magang hub lowongan (aman bila tabel belum ada)
Route::get('/alumni', function () {
    $alumni = Schema::hasTable('alumni')
        ? Alumni::with('mahasiswa.user', 'mahasiswa.prodi')->latest('id')->take(50)->get()
        : collect();

    return view('publik.alumni', compact('alumni'));
})->name('publik.alumni');

Route::get('/lowongan', function () {
    $lowongan = Schema::hasTable('magang_lowongan')
        ? MagangLowongan::with('mitra')->where('status', 'buka')->latest('id')->take(50)->get()
        : collect();

    return view('publik.lowongan', compact('lowongan'));
})->name('publik.lowongan');

// Karier publik (M-039): daftar lowongan + lamaran tanpa login.
Route::get('/karier', fn () => view('pages.livewire-page', ['component' => 'rekrutmen.lamar', 'title' => 'Karier']))->middleware('throttle:public')->name('publik.karier');

// M-055: kuesioner tracer publik (tanpa login, verifikasi NIM alumni).
Route::get('/tracer', fn () => view('pages.livewire-page', ['component' => 'tracer.isi', 'title' => 'Tracer Study']))->middleware('throttle:public')->name('publik.tracer');

// M-060: PWA portal mahasiswa — halaman fallback offline (di-cache service worker).
Route::get('/offline', fn () => view('publik.offline'))->name('publik.offline');
// Perpustakaan: OPAC publik (tanpa login).
Route::get('/katalog', fn () => view('pages.livewire-page', ['component' => 'perpus.katalog', 'title' => 'Katalog Perpustakaan']))->middleware('throttle:public')->name('publik.katalog');
// PMB publik
Route::get('/pmb', [PmbController::class, 'create'])->middleware('throttle:public')->name('pmb.create');
Route::post('/pmb', [PmbController::class, 'store'])->middleware('throttle:public')->name('pmb.store');
Route::get('/pmb/cek', [PmbController::class, 'cek'])->middleware('throttle:public')->name('pmb.cek');

// Verifikasi surat publik (QR) — nomor mengandung slash, tangkap semua segmen
Route::get('/surat/verifikasi/{nomor}', [SuratController::class, 'verify'])->where('nomor', '.*')->middleware('throttle:public')->name('surat.verify');
// Verifikasi SK mengajar publik (QR) — pola sama.
Route::get('/sk-mengajar/verifikasi/{nomor}', [SkMengajarController::class, 'verify'])->where('nomor', '.*')->middleware('throttle:public')->name('sk-mengajar.verify');
// Verifikasi publik dokumen akademik & KTM digital (QR)
Route::get('/khs/{mahasiswa}/verifikasi', [KhsController::class, 'verify'])->middleware('throttle:public')->name('khs.verify');
Route::get('/transkrip/{mahasiswa}/verifikasi', [TranskripController::class, 'verify'])->middleware('throttle:public')->name('transkrip.verify');
Route::get('/ktm/verifikasi/{nim}', [KtmController::class, 'verify'])->middleware('throttle:public')->name('ktm.verify');

// Callback payment gateway (signature diverifikasi di adapter)
Route::post('/payment/callback', [BayarController::class, 'callback'])->middleware('throttle:30,1')->name('payment.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cetak dokumen: kepemilikan dicek server-side di controller.
    Route::get('/krs/{krs}/cetak', [KrsController::class, 'cetak'])->name('krs.cetak');
    Route::get('/khs/{mahasiswaId}', [KhsController::class, 'show'])->name('khs.show');
    Route::get('/transkrip/{mahasiswaId}/cetak', [TranskripController::class, 'cetak'])->name('transkrip.cetak');
    Route::get('/ktm/{mahasiswa}/cetak', [KtmController::class, 'cetak'])->name('ktm.cetak');
    Route::get('/kwitansi/{pembayaran}/cetak', [KwitansiController::class, 'cetak'])->name('kwitansi.cetak');
    // Kuitansi gabungan: semua pembayaran terverifikasi 1 mhs + 1 periode.
    Route::get('/kwitansi/gabungan', [KwitansiController::class, 'gabungan'])->name('kwitansi.gabungan');
    // SK mengajar: dokumen + verifikasi QR (pola Surat, print-CSS).
    Route::get('/sk-mengajar/{dosen}/{tahun}/cetak', [SkMengajarController::class, 'cetak'])->name('sk-mengajar.cetak');
    Route::get('/surat/{surat}/cetak', [SuratController::class, 'cetak'])->name('surat.cetak');

    // Mahasiswa (+ staf pendamping)
    Route::middleware('role:mahasiswa|superadmin|admin_prodi')->group(function () {
        Route::get('/mahasiswa/krs', fn () => view('mahasiswa.krs-pengajuan'))->name('mahasiswa.krs.pengajuan');
        Route::get('/keuangan/bayar', [BayarController::class, 'index'])->name('keuangan.bayar');
        Route::post('/keuangan/bayar/{tagihan}/va', [BayarController::class, 'createVa'])->name('keuangan.bayar.va');
        Route::get('/keuangan/bayar/status/{pembayaran}', [BayarController::class, 'status'])->name('keuangan.bayar.status');
        Route::get('/keuangan/bayar-manual', fn () => view('pages.livewire-page', ['component' => 'keuangan.bayar-manual', 'title' => 'Bayar Manual']))->name('keuangan.bayar.manual');
        Route::get('/lms/tugas/kumpul', fn (Request $r) => view('lms.kumpul', [
            'tugasId' => $r->integer('tugas') ?: Tugas::latest('id')->value('id'),
            'mahasiswaId' => Mahasiswa::where('user_id', Auth::id())->value('id')
                ?? Mahasiswa::latest('id')->value('id'),
        ]))->name('lms.tugas.kumpul');
        Route::get('/absensi/scan', fn () => view('absensi.scan', [
            'mahasiswaId' => Mahasiswa::where('user_id', Auth::id())->value('id')
                ?? Mahasiswa::latest('id')->value('id'),
        ]))->name('absensi.scan');
        Route::get('/skripsi/pengajuan', fn () => view('pages.livewire-page', ['component' => 'skripsi.pengajuan-judul', 'title' => 'Pengajuan Judul']))->name('skripsi.pengajuan');
        Route::get('/bimbingan', fn () => view('pages.livewire-page', ['component' => 'bimbingan.log', 'title' => 'Log Bimbingan']))->name('bimbingan.log');
        Route::get('/sidang/pendaftaran', fn () => view('pages.livewire-page', ['component' => 'sidang.pendaftaran', 'title' => 'Pendaftaran Sidang']))->name('sidang.pendaftaran');
        Route::get('/surat/pengajuan', fn () => view('pages.livewire-page', ['component' => 'surat.pengajuan', 'title' => 'Pengajuan Surat']))->name('surat.pengajuan');
        Route::get('/kemahasiswaan/cuti', fn () => view('pages.livewire-page', ['component' => 'kemahasiswaan.cuti', 'title' => 'Cuti & Status Mahasiswa']))->name('kemahasiswaan.cuti');
    });

    // Dosen (+ staf akademik + pimpinan prodi/jurusan)
    Route::middleware('role:dosen|kaprodi|kajur|superadmin|admin_prodi')->group(function () {
        Route::get('/dosen/krs/persetujuan', fn () => view('dosen.krs-persetujuan'))->name('dosen.krs.persetujuan');
        Route::get('/dosen/wali', fn () => view('dosen.wali'))->name('dosen.wali');
        Route::get('/lms/materi', fn (Request $r) => view('lms.materi', [
            'pertemuanId' => $r->integer('pertemuan') ?: Pertemuan::latest('id')->value('id'),
        ]))->name('lms.materi');
        Route::get('/lms/tugas', fn (Request $r) => view('lms.tugas', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('lms.tugas');
        Route::get('/lms/bank-soal', fn () => view('pages.livewire-page', ['component' => 'lms.bank-soal', 'title' => 'Bank Soal']))->name('lms.bank');
        Route::get('/lms/bank-soal-impor', fn (Request $r) => view('lms.bank-soal-impor', ['mkId' => $r->integer('mk') ?: null]))->name('lms.bank.impor');
        Route::get('/lms/kuis', fn (Request $r) => view('lms.kuis', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('lms.kuis');
        Route::get('/nilai/input', fn (Request $r) => view('nilai.input', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('nilai.input');
        Route::get('/nilai/kunci', fn (Request $r) => view('nilai.kunci', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('nilai.kunci');
        Route::get('/skripsi/pembimbing', fn () => view('pages.livewire-page', ['component' => 'skripsi.set-pembimbing', 'title' => 'Penetapan Pembimbing']))->name('skripsi.pembimbing');
        Route::get('/sidang/nilai', fn () => view('pages.livewire-page', ['component' => 'sidang.nilai-sidang', 'title' => 'Nilai Sidang']))->name('sidang.nilai');
        Route::get('/riset/hibah', fn () => view('pages.livewire-page', ['component' => 'riset.hibah', 'title' => 'Hibah Riset']))->name('riset.hibah');
        Route::get('/riset/publikasi', fn () => view('pages.livewire-page', ['component' => 'riset.publikasi', 'title' => 'Publikasi & HKI']))->name('riset.publikasi');
        Route::get('/riset/pengabdian', fn () => view('pages.livewire-page', ['component' => 'riset.pengabdian', 'title' => 'Pengabdian Masyarakat']))->name('riset.pengabdian');
        Route::get('/bkd', fn () => view('pages.livewire-page', ['component' => 'bkd.kelola', 'title' => 'Beban Kerja Dosen']))->name('bkd.kelola');
    });

    // Keuangan & admin prodi (+ kasir & akuntansi)
    Route::middleware('role:keuangan|kasir|akuntansi|superadmin|admin_prodi')->group(function () {
        Route::get('/keuangan/tagihan', fn () => view('pages.livewire-page', ['component' => 'keuangan.tagihan-massal', 'title' => 'Tagihan Massal']))->name('keuangan.tagihan');
        Route::get('/keuangan/verifikasi', fn () => view('pages.livewire-page', ['component' => 'keuangan.verifikasi-bayar', 'title' => 'Verifikasi Pembayaran']))->name('keuangan.verifikasi');
        Route::get('/keuangan/beasiswa', fn () => view('pages.livewire-page', ['component' => 'beasiswa.kelola', 'title' => 'Beasiswa']))->name('keuangan.beasiswa');
        Route::get('/keuangan/denda', fn () => view('pages.livewire-page', ['component' => 'keuangan.denda-rule-kelola', 'title' => 'Aturan Denda']))->name('keuangan.denda');
        Route::post('/keuangan/refund/{pembayaran}', [BayarController::class, 'refund'])->middleware('throttle:30,1')->name('keuangan.refund');
        Route::get('/keuangan/tutup-buku', fn () => view('pages.livewire-page', ['component' => 'keuangan.tutup-buku', 'title' => 'Tutup Buku']))->name('keuangan.tutup-buku');
        Route::get('/keuangan/hutang', fn () => view('pages.livewire-page', ['component' => 'keuangan.hutang', 'title' => 'Utang Usaha']))->name('keuangan.hutang');
        Route::get('/keuangan/payroll', fn () => view('pages.livewire-page', ['component' => 'keuangan.payroll', 'title' => 'Penggajian']))->name('keuangan.payroll');
        Route::get('/keuangan/slip/{slip}/cetak', [SlipController::class, 'cetak'])->name('keuangan.slip.cetak');
        Route::get('/keuangan/gl', fn () => view('pages.livewire-page', ['component' => 'keuangan.gl', 'title' => 'Buku Besar']))->name('keuangan.gl');
        Route::get('/keuangan/anggaran', fn () => view('pages.livewire-page', ['component' => 'keuangan.anggaran', 'title' => 'Anggaran']))->name('keuangan.anggaran');
        // Ekspor rekap piutang aging (CSV per bucket umur).
        Route::get('/keuangan/piutang-aging', function (Request $request) {
            $laporan = KeuanganService::piutangAging($request->integer('tahun') ?: null);
            $nama = 'piutang-aging-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($laporan): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['nim', 'nama', 'jenis', 'jatuh_tempo', 'hari', 'sisa', 'bucket'], ';');

                foreach ($laporan['rows'] as $r) {
                    fputcsv($out, [$r['nim'], $r['nama'], $r['jenis'], $r['jatuh_tempo'], $r['hari'], $r['sisa'], $r['bucket']], ';');
                }

                fputcsv($out, ['TOTAL', '', '', '', '', array_sum($laporan['total']), json_encode($laporan['total'])], ';');
                fclose($out);
            }, $nama);
        })->name('keuangan.piutang-aging')->middleware('throttle:10,1');
        Route::get('/surat/nomor', fn () => view('pages.livewire-page', ['component' => 'surat.nomor-otomatis', 'title' => 'Nomor Surat']))->name('surat.nomor');
    });

    // Pimpinan (+ eksekutif universitas & fakultas)
    Route::middleware('role:pimpinan|rektor|warek|dekan|wadek|superadmin')->group(function () {
        Route::get('/dashboard/pimpinan', [DashboardController::class, 'pimpinan'])->name('dashboard.pimpinan');
        Route::get('/pddikti', [PddiktiController::class, 'index'])->name('pddikti.index');
        Route::get('/pddikti/export/{jenis}', [PddiktiController::class, 'export'])->middleware('throttle:30,1')->name('pddikti.export');
        Route::post('/pddikti/push', [PddiktiController::class, 'push'])->middleware('throttle:30,1')->name('pddikti.push');
        Route::get('/pddikti/status', [PddiktiController::class, 'status'])->name('pddikti.status');
        Route::post('/pddikti/sync', [PddiktiController::class, 'sync'])->middleware('throttle:30,1')->name('pddikti.sync');
        Route::post('/pddikti/sinta', [PddiktiController::class, 'sinta'])->middleware('throttle:30,1')->name('pddikti.sinta');
        Route::post('/pddikti/garuda', [PddiktiController::class, 'garuda'])->middleware('throttle:30,1')->name('pddikti.garuda');
        Route::post('/pddikti/tte', [PddiktiController::class, 'tte'])->middleware('throttle:30,1')->name('pddikti.tte');
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export/tagihan', [LaporanController::class, 'exportTagihan'])->name('laporan.export.tagihan');
        Route::get('/laporan/export/absensi', [LaporanController::class, 'exportAbsensi'])->name('laporan.export.absensi');
        Route::get('/pmb/funnel', [PmbFunnelController::class, 'index'])->name('pmb.funnel');
        Route::get('/laporan/export/tunggakan', [LaporanController::class, 'exportTunggakan'])->name('laporan.export.tunggakan');
        Route::get('/laporan/export/pmb', [LaporanController::class, 'exportPmb'])->name('laporan.export.pmb');
        Route::get('/laporan/export/krs', [LaporanController::class, 'exportKrs'])->name('laporan.export.krs');
        Route::get('/laporan/export/nilai', [LaporanController::class, 'exportNilai'])->name('laporan.export.nilai');
        Route::get('/laporan/export/neraca', [LaporanController::class, 'exportNeraca'])->name('laporan.export.neraca');
        Route::get('/laporan/eksekutif', fn () => view('pages.livewire-page', ['component' => 'analitik.eksekutif', 'title' => 'Analitik Eksekutif']))->name('laporan.eksekutif');
        Route::get('/skripsi/yudisium', fn () => view('pages.livewire-page', ['component' => 'skripsi.yudisium', 'title' => 'Yudisium']))->name('skripsi.yudisium');
    });

    // Admin platform
    Route::middleware('role:superadmin|admin_prodi')->group(function () {
        Route::get('/admin/pmb/verifikasi', fn () => view('admin.pmb-verifikasi'))->name('admin.pmb.verifikasi');
        // Arsip berkas pendaftar per gelombang (ZIP + manifest + audit).
        Route::get('/admin/pmb/arsip/{gelombang}', function (int $gelombang) {
            $path = PmbArsipService::arsipGelombang($gelombang, Auth::id());

            return response()->download($path, basename($path))->deleteFileAfterSend(true);
        })->name('admin.pmb.arsip')->middleware('throttle:5,1');
        // Opname aset: kondisi fisik vs buku + terapkan.
        Route::get('/aset/opname', fn () => view('pages.livewire-page', ['component' => 'aset.opname', 'title' => 'Opname Aset']))->name('aset.opname');
        // Asrama: alokasi kamar + iuran.
        Route::get('/asrama', fn () => view('pages.livewire-page', ['component' => 'asrama.kelola', 'title' => 'Asrama']))->name('asrama.kelola');
        Route::get('/admin/users', fn () => view('pages.livewire-page', ['component' => 'admin.user-data', 'title' => 'Kelola User']))->name('admin.users');
        Route::get('/admin/delegasi', fn () => view('pages.livewire-page', ['component' => 'admin.delegasi', 'title' => 'Delegasi Peran']))->name('admin.delegasi');
        Route::get('/admin/matriks-rbac', fn () => view('pages.livewire-page', ['component' => 'admin.matriks-rbac', 'title' => 'Matriks Peran']))->name('admin.matriks-rbac');
        Route::get('/admin/integrasi', fn () => view('pages.livewire-page', ['component' => 'admin.integrasi', 'title' => 'Integrasi']))->name('admin.integrasi');
        Route::get('/admin/master', fn () => view('pages.livewire-page', ['component' => 'master.data', 'title' => 'Data Master']))->name('admin.master');
        Route::get('/admin/dosen', fn () => view('pages.livewire-page', ['component' => 'admin.dosen-data', 'title' => 'Data Dosen & Tendik']))->name('admin.dosen');
        Route::get('/admin/kurikulum', fn () => view('pages.livewire-page', ['component' => 'master.kurikulum', 'title' => 'Kurikulum']))->name('admin.kurikulum');
        Route::get('/admin/impor', fn () => view('pages.livewire-page', ['component' => 'admin.impor-csv', 'title' => 'Impor CSV']))->name('admin.impor');
        Route::get('/admin/akreditasi', fn () => view('pages.livewire-page', ['component' => 'admin.akreditasi', 'title' => 'Akreditasi']))->name('admin.akreditasi');
        Route::get('/admin/organisasi', fn () => view('pages.livewire-page', ['component' => 'organisasi.kelola', 'title' => 'Organisasi Kemahasiswaan']))->name('admin.organisasi');
        Route::get('/admin/rekrutmen', fn () => view('pages.livewire-page', ['component' => 'rekrutmen.kelola', 'title' => 'Rekrutmen']))->name('admin.rekrutmen');
        Route::get('/admin/tracer', fn () => view('pages.livewire-page', ['component' => 'admin.tracer', 'title' => 'Tracer Study']))->name('admin.tracer');
        Route::get('/admin/jadwal', fn () => view('pages.livewire-page', ['component' => 'jadwal.generate', 'title' => 'Jadwal Kuliah']))->name('admin.jadwal');
        Route::get('/admin/impor/template/{jenis}', [LaporanController::class, 'templateImpor'])->name('admin.impor.template');
    });

    // OBE per prodi: admin penuh + kaprodi/kajur terkunci prodinya di komponen.
    Route::middleware('role:superadmin|admin_prodi|kaprodi|kajur')->group(function () {
        Route::get('/admin/obe', fn () => view('pages.livewire-page', ['component' => 'obe.kelola', 'title' => 'OBE / CPL-CPMK']))->name('admin.obe');
        // Draf borang akreditasi (ekspor CSV, bukan klaim).
        Route::get('/admin/akreditasi/borang/{prodi}', function (int $prodi) {
            $ringkas = BorangService::ringkasan($prodi);
            $nama = 'borang-prodi-'.$prodi.'-'.now()->format('Ymd-His').'.csv';

            return response()->streamDownload(function () use ($ringkas): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['BAGIAN', 'A', 'B', 'C'], ';');
                fputcsv($out, ['profil', 'aktif:'.$ringkas['profil']['aktif'], 'lulus:'.$ringkas['profil']['lulus'], 'cuti:'.$ringkas['profil']['cuti']], ';');

                foreach ($ringkas['cpmk'] as $c) {
                    fputcsv($out, ['cpmk', $c['kode'], $c['mk'], 'asesmen:'.$c['asesmen']], ';');
                }

                foreach ($ringkas['tracer'] as $t) {
                    fputcsv($out, ['tracer', $t['tahun'], 'responden:'.$t['responden'], 'terserap:'.$t['terserap']], ';');
                }

                foreach ($ringkas['dokumen'] as $d) {
                    fputcsv($out, ['dokumen', $d['kategori'], 'total:'.$d['total'], 'terverifikasi:'.$d['terverifikasi']], ';');
                }

                fclose($out);
            }, $nama);
        })->name('admin.akreditasi.borang')->middleware('throttle:10,1');
    });

    // Perpustakaan: sirkulasi staf + OPAC publik.
    Route::middleware('role:pustakawan|superadmin|admin_prodi')->group(function () {
        Route::get('/perpus/sirkulasi', fn () => view('pages.livewire-page', ['component' => 'perpus.sirkulasi', 'title' => 'Sirkulasi Perpustakaan']))->name('perpus.sirkulasi');
    });

    // Klinik: data sensitif — gate permission terpisah (BUKAN grup admin),
    // + can() di komponen + audit akses per rekam medis.
    Route::middleware('permission:klinik.manage')->group(function () {
        Route::get('/klinik/kunjungan', fn () => view('pages.livewire-page', ['component' => 'klinik.kunjungan', 'title' => 'Klinik']))->name('klinik.kunjungan');
    });

    // Kalender & helpdesk: semua user login (kepemilikan dicek di komponen)
    Route::get('/profil', fn () => view('pages.livewire-page', ['component' => 'profil', 'title' => 'Profil Saya']))->name('profil');
    Route::get('/lms/forum', fn (Request $r) => view('lms.forum', ['kelasId' => $r->integer('kelas') ?: null]))->name('lms.forum');
    Route::get('/lms/kuis-kerjakan', fn (Request $r) => view('lms.kuis-kerjakan', ['quizId' => $r->integer('quiz') ?: null]))->name('lms.kuis.kerjakan');
    // M-030/031: paket offline SCORM per pertemuan (semua login).
    Route::get('/lms/paket/{pertemuan}', function (int $pertemuan) {
        $path = ScormService::paket($pertemuan);

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    })->name('lms.paket')->middleware('throttle:10,1');
    // Ide bank: paket massal per kelas (semua pertemuan dalam satu ZIP).
    Route::get('/lms/paket-kelas/{kelas}', function (int $kelas) {
        $path = ScormService::paketKelas($kelas);

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    })->name('lms.paket.kelas')->middleware('throttle:10,1');
    Route::get('/cari', [PencarianController::class, 'index'])->name('pencarian');
    Route::get('/kalender', fn () => view('pages.livewire-page', ['component' => 'kalender.kelola', 'title' => 'Kalender Akademik']))->name('kalender.index');
    Route::get('/kalender.ics', [KalenderController::class, 'ics'])->name('kalender.ics');
    Route::get('/helpdesk', fn () => view('pages.livewire-page', ['component' => 'helpdesk.tiket', 'title' => 'Helpdesk']))->name('helpdesk.index');
    // M-055: alumni memutakhirkan kariernya sendiri.
    Route::get('/alumni/karier', fn () => view('pages.livewire-page', ['component' => 'alumni.karier', 'title' => 'Karier Saya']))->name('alumni.karier');
    // M-041: reservasi lab/fasilitas — semua user login bisa mengajukan,
    // persetujuan digerbang di komponen (superadmin/admin_prodi).
    Route::get('/fasilitas/pinjam', fn () => view('pages.livewire-page', ['component' => 'jadwal.pinjam-ruang', 'title' => 'Pinjam Ruang & Fasilitas']))->name('fasilitas.pinjam');
    // Kalender pemakaian ruang mingguan (baca-saja, semua login).
    Route::get('/fasilitas/kalender', fn (Request $r) => view('pages.livewire-page', ['component' => 'jadwal.kalender-ruang', 'title' => 'Kalender Ruang']))->name('fasilitas.kalender');

    // Audit viewer: pola permission Spatie (M-016) — semua pemegang laporan.view.
    Route::middleware('permission:laporan.view')->group(function () {
        Route::get('/admin/audit', [AuditController::class, 'index'])->name('admin.audit');
    });

    // Jalur examiner mandiri (owner-scope; tanpa akses NilaiSidang penuh).
    Route::middleware('role:penguji|superadmin|admin_prodi')->group(function () {
        Route::get('/sidang/nilai-saya', fn () => view('pages.livewire-page', ['component' => 'sidang.nilai-penguji', 'title' => 'Nilai Sidang Saya']))->name('sidang.nilai.milikku');
    });

    // Staf internal
    Route::middleware('role:superadmin|admin_prodi|keuangan|pimpinan')->group(function () {
        Route::get('/notifikasi/broadcast', fn () => view('pages.livewire-page', ['component' => 'notifikasi.broadcast', 'title' => 'Broadcast Notifikasi']))->name('notifikasi.broadcast');
        Route::get('/media/generate', fn () => view('pages.livewire-page', ['component' => 'media.generator', 'title' => 'Generate Gambar AI']))->name('media.generate');
    });

    // Pengumuman: gate permission terpisah (pengumuman.manage).
    Route::middleware('permission:pengumuman.manage')->group(function () {
        Route::get('/notifikasi/pengumuman', fn () => view('pages.livewire-page', ['component' => 'notifikasi.pengumuman-kelola', 'title' => 'Kelola Pengumuman']))->name('notifikasi.pengumuman');
    });

    // SDM: payroll + rekrutmen (hrd tidak masuk grup keuangan penuh — SoD).
    Route::middleware('role:hrd|superadmin|admin_prodi')->group(function () {
        Route::get('/hrd/payroll', fn () => view('pages.livewire-page', ['component' => 'keuangan.payroll', 'title' => 'Penggajian']))->name('hrd.payroll');
        Route::get('/hrd/rekrutmen', fn () => view('pages.livewire-page', ['component' => 'rekrutmen.kelola', 'title' => 'Rekrutmen']))->name('hrd.rekrutmen');
    });

    // AI & analitik: semua user login (kepemilikan/role dicek di komponen/controller)
    Route::get('/ai/chat', fn () => view('pages.livewire-page', ['component' => 'ai.chatbot', 'title' => 'Chatbot Akademik']))->name('ai.chat');
    Route::get('/analitik/at-risk', fn () => view('pages.livewire-page', ['component' => 'analitik.at-risk', 'title' => 'Prediktor At-Risk']))->name('analitik.at-risk');
});
