<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\BayarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\KtmController;
use App\Http\Controllers\KwitansiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PddiktiController;
use App\Http\Controllers\PmbController;
use App\Http\Controllers\PmbFunnelController;
use App\Http\Controllers\PublikController;
use App\Http\Controllers\SessionAuthController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Health check publik (load balancer / monitoring).
Route::get('/health', function () {
    try {
        DB::select('select 1');

        return response()->json(['ok' => true, 'db' => 'up', 'time' => now()->toIso8601String()]);
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
        'piutang' => $safe('tagihan', fn () => Tagihan::whereIn('status', ['belum_bayar', 'sebagian'])->sum(DB::raw('nominal - terbayar'))),
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
            $total = (float) Tagihan::sum('nominal');
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

// PMB publik
Route::get('/pmb', [PmbController::class, 'create'])->middleware('throttle:public')->name('pmb.create');
Route::post('/pmb', [PmbController::class, 'store'])->middleware('throttle:public')->name('pmb.store');
Route::get('/pmb/cek', [PmbController::class, 'cek'])->middleware('throttle:public')->name('pmb.cek');

// Verifikasi surat publik (QR) — nomor mengandung slash, tangkap semua segmen
Route::get('/surat/verifikasi/{nomor}', [SuratController::class, 'verify'])->where('nomor', '.*')->middleware('throttle:public')->name('surat.verify');
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

    // Dosen (+ staf akademik)
    Route::middleware('role:dosen|superadmin|admin_prodi')->group(function () {
        Route::get('/dosen/krs/persetujuan', fn () => view('dosen.krs-persetujuan'))->name('dosen.krs.persetujuan');
        Route::get('/dosen/wali', fn () => view('dosen.wali'))->name('dosen.wali');
        Route::get('/lms/materi', fn (Request $r) => view('lms.materi', [
            'pertemuanId' => $r->integer('pertemuan') ?: Pertemuan::latest('id')->value('id'),
        ]))->name('lms.materi');
        Route::get('/lms/tugas', fn (Request $r) => view('lms.tugas', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('lms.tugas');
        Route::get('/nilai/input', fn (Request $r) => view('nilai.input', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('nilai.input');
        Route::get('/nilai/kunci', fn (Request $r) => view('nilai.kunci', [
            'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
        ]))->name('nilai.kunci');
        Route::get('/skripsi/pembimbing', fn () => view('pages.livewire-page', ['component' => 'skripsi.set-pembimbing', 'title' => 'Penetapan Pembimbing']))->name('skripsi.pembimbing');
        Route::get('/sidang/nilai', fn () => view('pages.livewire-page', ['component' => 'sidang.nilai-sidang', 'title' => 'Nilai Sidang']))->name('sidang.nilai');
    });

    // Keuangan & admin prodi
    Route::middleware('role:keuangan|superadmin|admin_prodi')->group(function () {
        Route::get('/keuangan/tagihan', fn () => view('pages.livewire-page', ['component' => 'keuangan.tagihan-massal', 'title' => 'Tagihan Massal']))->name('keuangan.tagihan');
        Route::get('/keuangan/verifikasi', fn () => view('pages.livewire-page', ['component' => 'keuangan.verifikasi-bayar', 'title' => 'Verifikasi Pembayaran']))->name('keuangan.verifikasi');
        Route::get('/keuangan/beasiswa', fn () => view('pages.livewire-page', ['component' => 'beasiswa.kelola', 'title' => 'Beasiswa']))->name('keuangan.beasiswa');
        Route::get('/keuangan/denda', fn () => view('pages.livewire-page', ['component' => 'keuangan.denda-rule-kelola', 'title' => 'Aturan Denda']))->name('keuangan.denda');
        Route::post('/keuangan/refund/{pembayaran}', [BayarController::class, 'refund'])->name('keuangan.refund');
        Route::get('/surat/nomor', fn () => view('pages.livewire-page', ['component' => 'surat.nomor-otomatis', 'title' => 'Nomor Surat']))->name('surat.nomor');
    });

    // Pimpinan
    Route::middleware('role:pimpinan|superadmin')->group(function () {
        Route::get('/dashboard/pimpinan', [DashboardController::class, 'pimpinan'])->name('dashboard.pimpinan');
        Route::get('/pddikti', [PddiktiController::class, 'index'])->name('pddikti.index');
        Route::get('/pddikti/export/{jenis}', [PddiktiController::class, 'export'])->name('pddikti.export');
        Route::post('/pddikti/push', [PddiktiController::class, 'push'])->name('pddikti.push');
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
        Route::get('/skripsi/yudisium', fn () => view('pages.livewire-page', ['component' => 'skripsi.yudisium', 'title' => 'Yudisium']))->name('skripsi.yudisium');
    });

    // Admin platform
    Route::middleware('role:superadmin|admin_prodi')->group(function () {
        Route::get('/admin/pmb/verifikasi', fn () => view('admin.pmb-verifikasi'))->name('admin.pmb.verifikasi');
        Route::get('/admin/users', fn () => view('pages.livewire-page', ['component' => 'admin.user-data', 'title' => 'Kelola User']))->name('admin.users');
        Route::get('/admin/integrasi', fn () => view('pages.livewire-page', ['component' => 'admin.integrasi', 'title' => 'Integrasi']))->name('admin.integrasi');
        Route::get('/admin/master', fn () => view('pages.livewire-page', ['component' => 'master.data', 'title' => 'Data Master']))->name('admin.master');
        Route::get('/admin/dosen', fn () => view('pages.livewire-page', ['component' => 'admin.dosen-data', 'title' => 'Data Dosen & Tendik']))->name('admin.dosen');
        Route::get('/admin/kurikulum', fn () => view('pages.livewire-page', ['component' => 'master.kurikulum', 'title' => 'Kurikulum']))->name('admin.kurikulum');
        Route::get('/admin/impor', fn () => view('pages.livewire-page', ['component' => 'admin.impor-csv', 'title' => 'Impor CSV']))->name('admin.impor');
        Route::get('/admin/jadwal', fn () => view('pages.livewire-page', ['component' => 'jadwal.generate', 'title' => 'Jadwal Kuliah']))->name('admin.jadwal');
        Route::get('/admin/impor/template/{jenis}', [LaporanController::class, 'templateImpor'])->name('admin.impor.template');
    });

    // Kalender & helpdesk: semua user login (kepemilikan dicek di komponen)
    Route::get('/profil', fn () => view('pages.livewire-page', ['component' => 'profil', 'title' => 'Profil Saya']))->name('profil');
    Route::get('/kalender', fn () => view('pages.livewire-page', ['component' => 'kalender.kelola', 'title' => 'Kalender Akademik']))->name('kalender.index');
    Route::get('/kalender.ics', [KalenderController::class, 'ics'])->name('kalender.ics');
    Route::get('/helpdesk', fn () => view('pages.livewire-page', ['component' => 'helpdesk.tiket', 'title' => 'Helpdesk']))->name('helpdesk.index');

    // Audit viewer: admin platform + pimpinan
    Route::middleware('role:superadmin|admin_prodi|pimpinan')->group(function () {
        Route::get('/admin/audit', [AuditController::class, 'index'])->name('admin.audit');
    });

    // Staf internal
    Route::middleware('role:superadmin|admin_prodi|keuangan|pimpinan')->group(function () {
        Route::get('/notifikasi/broadcast', fn () => view('pages.livewire-page', ['component' => 'notifikasi.broadcast', 'title' => 'Broadcast Notifikasi']))->name('notifikasi.broadcast');
        Route::get('/media/generate', fn () => view('pages.livewire-page', ['component' => 'media.generator', 'title' => 'Generate Gambar AI']))->name('media.generate');
    });

    // AI & analitik: semua user login (kepemilikan/role dicek di komponen/controller)
    Route::get('/ai/chat', fn () => view('pages.livewire-page', ['component' => 'ai.chatbot', 'title' => 'Chatbot Akademik']))->name('ai.chat');
    Route::get('/analitik/at-risk', fn () => view('pages.livewire-page', ['component' => 'analitik.at-risk', 'title' => 'Prediktor At-Risk']))->name('analitik.at-risk');
});
