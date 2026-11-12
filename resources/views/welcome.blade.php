@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', config('app.name', 'SIMAK Kuliah') . ' — Campus Operating System')

@section('content')
{{-- HERO --}}
<div class="card card-lg bg-dark text-white mb-0">
    <div class="card-body py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <div class="text-info mb-2">
                    <span class="status-dot status-dot-animated bg-info"></span>
                    Campus operating system untuk Indonesia
                </div>
                <h1 class="display-5 text-white">Satu kendali untuk setiap fase perjalanan mahasiswa</h1>
                <p class="text-white-50">Dari PMB, KRS, presensi, nilai sampai tagihan dan transkrip — data akademik tidak lagi tercecer di spreadsheet yang tidak saling bicara.</p>
                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('login') }}" class="btn btn-info">Coba demo</a>
                    <a href="{{ route('publik.prodi') }}" class="btn btn-outline-light">Lihat dokumentasi</a>
                </div>
                <div class="row mt-4 text-white-50 small">
                    <div class="col-6 mb-1">✓ UUID-ready</div>
                    <div class="col-6 mb-1">✓ Role &amp; scope</div>
                    <div class="col-6">✓ Audit trail</div>
                    <div class="col-6">✓ API v1</div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card bg-dark text-white">
                    <div class="card-header border-0">
                        <span class="me-1"><span class="badge badge-dot bg-red"></span></span>
                        <span class="me-1"><span class="badge badge-dot bg-yellow"></span></span>
                        <span class="me-3"><span class="badge badge-dot bg-green"></span></span>
                        <span class="badge bg-dark-lt text-white-50">local/app/admin</span>
                    </div>
                    <div class="card-body">
                        <div class="row row-cards">
                            <div class="col-4">
                                <div class="card card-sm bg-white text-dark">
                                    <div class="card-body text-center">
                                        <div class="text-secondary small">mahasiswa aktif semester ini</div>
                                        <div class="display-6">{{ $stats['mahasiswa'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-sm bg-primary text-white">
                                    <div class="card-body text-center">
                                        <div class="small opacity-75">Piutang mahasiswa</div>
                                        <div class="h2 text-white mb-1">Rp {{ number_format($stats['piutang'] ?? 0, 0, ',', '.') }}</div>
                                        <div class="small opacity-75">saldo invoice dari database</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card card-sm bg-dark-lt text-white">
                                    <div class="card-body text-center">
                                        <div class="small opacity-75">KRS menunggu persetujuan</div>
                                        <div class="display-6">{{ $stats['krs_pending'] ?? 0 }}</div>
                                        <div class="small text-success">{{ $stats['krs_ok'] ?? 0 }} disetujui hari ini</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- STATS STRIP --}}
<div class="card card-lg mt-3">
    <div class="card-body">
        <div class="row text-center">
            <div class="col-3"><div class="display-6">{{ $stats['mahasiswa'] ?? 0 }}</div><div class="text-secondary">Profil mahasiswa</div></div>
            <div class="col-3"><div class="display-6">{{ $stats['prodi'] ?? 0 }}</div><div class="text-secondary">Program studi</div></div>
            <div class="col-3"><div class="display-6">{{ $stats['matakuliah'] ?? 0 }}</div><div class="text-secondary">Mata kuliah</div></div>
            <div class="col-3"><div class="display-6">{{ $stats['audit'] ?? 0 }}</div><div class="text-secondary">Audit tercatat</div></div>
        </div>
    </div>
</div>

{{-- LIFECYCLE --}}
<div class="row mt-4">
    <div class="col-lg-3">
        <div class="text-info text-uppercase small mb-1">Lifecycle dalam satu peta</div>
        <h2>Data bergerak mengikuti proses kampus.</h2>
        <p class="text-secondary">Campus ERP menjaga konteks setiap keputusan: siapa yang mengubah, di semester mana, disetujui oleh siapa, dan apa dampaknya ke mahasiswa.</p>
    </div>
    <div class="col-lg-9">
        <div class="row row-cards">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="badge bg-blue-lt mb-2">01</span>
                        <h3 class="card-title">PMB → Mahasiswa</h3>
                        <p class="text-secondary">Pendaftaran, seleksi, registrasi ulang, dan generate NIM.</p>
                        <a href="{{ route('pmb.create') }}">Pelajari alurnya →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="badge bg-green-lt mb-2">02</span>
                        <h3 class="card-title">Akademik → Transkrip</h3>
                        <p class="text-secondary">Kurikulum, offering, KRS, presensi, nilai, KHS, IPK.</p>
                        <a href="{{ route('mahasiswa.krs.pengajuan') }}">Pelajari alurnya →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="badge bg-yellow-lt mb-2">03</span>
                        <h3 class="card-title">Keuangan → Laporan</h3>
                        <p class="text-secondary">Invoice, allocation, ledger, dashboard eksekutif, dan export.</p>
                        <a href="{{ route('keuangan.bayar') }}">Pelajari alurnya →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- PERAN --}}
<div class="card card-lg bg-light mt-4">
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3">
                <div class="text-info text-uppercase small mb-1">Dibuat untuk peran yang berbeda</div>
                <h2>Satu sumber data, tampilan yang relevan.</h2>
                <p class="text-secondary">Rektor melihat pertumbuhan dan risiko. BAAK melihat antrian administrasi. Dosen wali melihat KRS bimbingannya. Mahasiswa melihat langkah berikutnya.</p>
            </div>
            <div class="col-lg-9">
                <div class="row row-cards">
                    <div class="col-md-3">
                        <div class="card h-100 bg-dark text-white">
                            <div class="card-body">
                                <span class="avatar bg-dark-lt mb-2">R</span>
                                <h3 class="card-title text-white">Rektor</h3>
                                <p class="text-white-50">Retention &amp; graduation</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <span class="avatar bg-blue-lt mb-2">B</span>
                                <h3 class="card-title">BAAK</h3>
                                <p class="text-secondary">KRS &amp; status mahasiswa</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <span class="avatar bg-green-lt mb-2">F</span>
                                <h3 class="card-title">Finance</h3>
                                <p class="text-secondary">Piutang &amp; payment ledger</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <span class="avatar bg-yellow-lt mb-2">M</span>
                                <h3 class="card-title">Mahasiswa</h3>
                                <p class="text-secondary">KHS &amp; layanan mandiri</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CTA BAND --}}
<div class="card card-lg bg-dark text-white mt-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="text-white">Mulai dari fondasi akademik yang bisa dipercaya.</h2>
                <p class="text-white-50 mb-0">Demo data sudah tersedia. Masuk sebagai admin, BAAK, finance, dosen wali, atau mahasiswa.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('login') }}" class="btn btn-info">Buka akun demo</a>
            </div>
        </div>
    </div>
</div>

<footer class="d-flex justify-content-between text-secondary small mt-3">
    <span>© {{ date('Y') }} SIMAK Kuliah</span>
    <span>
        <a href="{{ route('publik.prodi') }}" class="me-3">Program Studi</a>
        <a href="{{ route('publik.pengumuman') }}" class="me-3">Pengumuman</a>
        <a href="{{ route('pmb.create') }}">PMB</a>
    </span>
</footer>
@endsection
