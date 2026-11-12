<?php

use App\Http\Controllers\BayarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\KrsController;
use App\Http\Controllers\KwitansiController;
use App\Http\Controllers\PddiktiController;
use App\Http\Controllers\PmbController;
use App\Http\Controllers\PublikController;
use App\Http\Controllers\SessionAuthController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\TranskripController;
use App\Models\AuditLog;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Pertemuan;
use App\Models\Prodi;
use App\Models\Tagihan;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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
    ];

    return view('welcome', compact('stats'));
})->name('home');

Route::get('/login', [SessionAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [SessionAuthController::class, 'login'])->name('login.attempt');

Route::get('/prodi', [PublikController::class, 'prodi'])->name('publik.prodi');
Route::get('/prodi/{slug}', [PublikController::class, 'prodiDetail'])->name('publik.prodi.detail');
Route::get('/pengumuman', [PublikController::class, 'pengumuman'])->name('publik.pengumuman');
Route::get('/pengumuman/{id}', [PublikController::class, 'pengumumanDetail'])->name('publik.pengumuman.detail');

// PMB publik
Route::get('/pmb', [PmbController::class, 'create'])->name('pmb.create');
Route::post('/pmb', [PmbController::class, 'store'])->name('pmb.store');
Route::get('/pmb/cek', [PmbController::class, 'cek'])->name('pmb.cek');

// Verifikasi surat publik (QR) — nomor mengandung slash, tangkap semua segmen
Route::get('/surat/verifikasi/{nomor}', [SuratController::class, 'verify'])->where('nomor', '.*')->name('surat.verify');

// Callback payment gateway (signature diverifikasi di adapter)
Route::post('/payment/callback', [BayarController::class, 'callback'])->name('payment.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/pimpinan', [DashboardController::class, 'pimpinan'])->name('dashboard.pimpinan');

    // PMB (admin)
    Route::get('/admin/pmb/verifikasi', fn () => view('admin.pmb-verifikasi'))->name('admin.pmb.verifikasi');

    // KRS
    Route::get('/mahasiswa/krs', fn () => view('mahasiswa.krs-pengajuan'))->name('mahasiswa.krs.pengajuan');
    Route::get('/krs/{krs}/cetak', [KrsController::class, 'cetak'])->name('krs.cetak');
    Route::get('/dosen/krs/persetujuan', fn () => view('dosen.krs-persetujuan'))->name('dosen.krs.persetujuan');

    // LMS (id via ?kelas= / ?pertemuan= / ?tugas=, default data terbaru)
    Route::get('/lms/materi', fn (Request $r) => view('lms.materi', [
        'pertemuanId' => $r->integer('pertemuan') ?: Pertemuan::latest('id')->value('id'),
    ]))->name('lms.materi');
    Route::get('/lms/tugas', fn (Request $r) => view('lms.tugas', [
        'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
    ]))->name('lms.tugas');
    Route::get('/lms/tugas/kumpul', fn (Request $r) => view('lms.kumpul', [
        'tugasId' => $r->integer('tugas') ?: Tugas::latest('id')->value('id'),
        'mahasiswaId' => Mahasiswa::where('user_id', Auth::id())->value('id')
            ?? Mahasiswa::latest('id')->value('id'),
    ]))->name('lms.tugas.kumpul');
    Route::get('/absensi/scan', fn () => view('absensi.scan', [
        'mahasiswaId' => Mahasiswa::where('user_id', Auth::id())->value('id')
            ?? Mahasiswa::latest('id')->value('id'),
    ]))->name('absensi.scan');

    // Nilai & KHS
    Route::get('/nilai/input', fn (Request $r) => view('nilai.input', [
        'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
    ]))->name('nilai.input');
    Route::get('/nilai/kunci', fn (Request $r) => view('nilai.kunci', [
        'kelasId' => $r->integer('kelas') ?: Kelas::latest('id')->value('id'),
    ]))->name('nilai.kunci');
    Route::get('/khs/{mahasiswaId}', [KhsController::class, 'show'])->name('khs.show');
    Route::get('/transkrip/{mahasiswaId}/cetak', [TranskripController::class, 'cetak'])->name('transkrip.cetak');

    // Skripsi / Sidang / Bimbingan
    Route::get('/skripsi/pengajuan', fn () => view('pages.livewire-page', ['component' => 'skripsi.pengajuan-judul', 'title' => 'Pengajuan Judul']))->name('skripsi.pengajuan');
    Route::get('/skripsi/pembimbing', fn () => view('pages.livewire-page', ['component' => 'skripsi.set-pembimbing', 'title' => 'Penetapan Pembimbing']))->name('skripsi.pembimbing');
    Route::get('/bimbingan', fn () => view('pages.livewire-page', ['component' => 'bimbingan.log', 'title' => 'Log Bimbingan']))->name('bimbingan.log');
    Route::get('/sidang/pendaftaran', fn () => view('pages.livewire-page', ['component' => 'sidang.pendaftaran', 'title' => 'Pendaftaran Sidang']))->name('sidang.pendaftaran');
    Route::get('/sidang/nilai', fn () => view('pages.livewire-page', ['component' => 'sidang.nilai-sidang', 'title' => 'Nilai Sidang']))->name('sidang.nilai');

    // Keuangan
    Route::get('/keuangan/tagihan', fn () => view('pages.livewire-page', ['component' => 'keuangan.tagihan-massal', 'title' => 'Tagihan Massal']))->name('keuangan.tagihan');
    Route::get('/keuangan/verifikasi', fn () => view('pages.livewire-page', ['component' => 'keuangan.verifikasi-bayar', 'title' => 'Verifikasi Pembayaran']))->name('keuangan.verifikasi');
    Route::get('/kwitansi/{pembayaran}/cetak', [KwitansiController::class, 'cetak'])->name('kwitansi.cetak');
    Route::get('/keuangan/bayar', [BayarController::class, 'index'])->name('keuangan.bayar');
    Route::post('/keuangan/bayar/{tagihan}/va', [BayarController::class, 'createVa'])->name('keuangan.bayar.va');
    Route::get('/keuangan/bayar/status/{pembayaran}', [BayarController::class, 'status'])->name('keuangan.bayar.status');

    // Surat
    Route::get('/surat/pengajuan', fn () => view('pages.livewire-page', ['component' => 'surat.pengajuan', 'title' => 'Pengajuan Surat']))->name('surat.pengajuan');
    Route::get('/surat/nomor', fn () => view('pages.livewire-page', ['component' => 'surat.nomor-otomatis', 'title' => 'Nomor Surat']))->name('surat.nomor');
    Route::get('/surat/{surat}/cetak', [SuratController::class, 'cetak'])->name('surat.cetak');

    // Media AI (ComfyUI)
    Route::get('/media/generate', fn () => view('pages.livewire-page', ['component' => 'media.generator', 'title' => 'Generate Gambar AI']))->name('media.generate');

    // Notifikasi broadcast
    Route::get('/notifikasi/broadcast', fn () => view('pages.livewire-page', ['component' => 'notifikasi.broadcast', 'title' => 'Broadcast Notifikasi']))->name('notifikasi.broadcast');

    // PDDikti
    Route::get('/pddikti', [PddiktiController::class, 'index'])->name('pddikti.index');
    Route::get('/pddikti/export/{jenis}', [PddiktiController::class, 'export'])->name('pddikti.export');
    Route::post('/pddikti/push', [PddiktiController::class, 'push'])->name('pddikti.push');

    // Admin integrasi
    Route::get('/admin/integrasi', fn () => view('pages.livewire-page', ['component' => 'admin.integrasi', 'title' => 'Integrasi']))->name('admin.integrasi');
});
