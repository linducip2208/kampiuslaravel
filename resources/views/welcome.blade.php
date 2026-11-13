@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', 'Kampus ERP — Sistem Informasi Kampus Terpadu')

@section('content')
{{-- ============ NAVBAR ============ --}}
<header class="navbar navbar-expand-md d-print-none sticky-top bg-white border-bottom">
    <div class="container-xl" style="max-width: 1280px">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landing-menu" aria-label="Menu navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ route('home') }}" class="text-decoration-none"><span class="avatar avatar-sm bg-primary text-white me-1">🎓</span><strong>Kampus</strong> <span class="text-secondary fw-normal small">University ERP</span></a>
        </h1>
        <div class="collapse navbar-collapse" id="landing-menu">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link active" href="{{ route('home') }}"><span class="badge bg-primary">Beranda</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                <li class="nav-item"><a class="nav-link" href="#modul">Modul</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('dokumen.index') }}">Dokumentasi</a></li>
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
            </ul>
            <div class="ms-auto d-flex gap-2 align-items-center">
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Login</a>
                <a href="{{ route('login') }}" class="btn btn-primary">Demo Gratis →</a>
            </div>
        </div>
    </div>
</header>

{{-- ============ HERO ============ --}}
@php
    $heroImg = file_exists(public_path('img/kampus-hero.png')) ? asset('img/kampus-hero.png') : null;
@endphp
<div class="bg-blue-lt" @if($heroImg) style="background: linear-gradient(100deg, rgba(255,255,255,.96) 30%, rgba(255,255,255,.55) 55%, rgba(255,255,255,.15) 100%), url('{{ $heroImg }}') center/cover no-repeat;" @endif>
    <div class="container-xl py-5" style="max-width: 1280px">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <span class="badge bg-blue-lt text-primary mb-3">● Sistem Informasi Kampus Terpadu</span>
                <h1 class="display-4 fw-bold">Kampus ERP Solusi Lengkap Manajemen Perguruan Tinggi</h1>
                <p class="text-secondary">Kelola seluruh proses akademik, keuangan dan administrasi kampus dalam satu sistem yang modern, aman dan mudah digunakan.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('pmb.create') }}" class="btn btn-primary btn-lg">✈ Mulai Sekarang →</a>
                    <a href="{{ route('login') }}" class="btn btn-white btn-lg">▷ Lihat Demo</a>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-3 small">
                    <span>✓ Modern UI/UX</span><span>✓ Aman &amp; Terpercaya</span><span>✓ Support Penuh</span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="row g-0">
                        <div class="col-md-3 bg-dark text-white d-none d-md-block" style="border-radius: .5rem 0 0 .5rem">
                            <div class="p-2">
                                <div class="d-flex align-items-center gap-1 mb-2"><span class="avatar avatar-xs bg-primary text-white">🎓</span><strong class="small">Kampus</strong></div>
                                <div class="nav flex-column small">
                                    <span class="text-white bg-blue rounded px-2 py-1 mb-1">▦ Dashboard</span>
                                    <span class="px-2 py-1 text-white-50">📖 Akademik</span>
                                    <span class="px-2 py-1 text-white-50">💳 Keuangan</span>
                                    <span class="px-2 py-1 text-white-50">👥 Kepegawaian</span>
                                    <span class="px-2 py-1 text-white-50">📝 PMB</span>
                                    <span class="px-2 py-1 text-white-50">📊 Laporan</span>
                                    <span class="px-2 py-1 text-white-50">⚙ Pengaturan</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="card-header border-0 py-2">
                                <h3 class="card-title mb-0">Dashboard</h3>
                                <div class="card-actions"><span class="badge bg-green-lt">● Admin ▾</span></div>
                            </div>
                            <div class="card-body bg-light">
                                <div class="row row-cards mb-3">
                                    @php
                                        $statCards = [
                                            [number_format($stats['mahasiswa'] ?? 0), 'Total Mahasiswa', 'bg-blue-lt'],
                                            [number_format($stats['dosen'] ?? 0), 'Dosen Aktif', 'bg-green-lt'],
                                            [number_format($stats['matakuliah'] ?? 0), 'Mata Kuliah', 'bg-yellow-lt'],
                                            [(int) ($stats['krs_pct'] ?? 0).'%', 'KRS Disetujui', 'bg-purple-lt'],
                                        ];
                                    @endphp
                                    @foreach($statCards as [$nilai, $label, $warna])
                                        <div class="col-6 col-md-3">
                                            <div class="card card-sm"><div class="card-body text-center p-2">
                                                <span class="badge {{ $warna }} mb-1">●</span>
                                                <div class="h3 mb-0">{{ $nilai }}</div>
                                                <div class="text-secondary small">{{ $label }}</div>
                                            </div></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="row row-cards">
                                    <div class="col-md-7">
                                        <div class="card h-100"><div class="card-body p-2">
                                            <h4 class="card-title">Statistik Akademik</h4>
                                            @php
                                                $bl = $stats['bayar_bulanan'] ?? ['labels' => [], 'nominal' => []];
                                                $vals = $bl['nominal']; $labs = $bl['labels'];
                                                $maks = max(1, max($vals ?: [0]));
                                                $w = 100; $h = 26; $titik = [];
                                                foreach ($vals as $i => $v) {
                                                    $x = 6 + $i * (($w - 12) / max(1, count($vals) - 1));
                                                    $y = $h - 3 - ($v / $maks) * ($h - 9);
                                                    $titik[] = round($x, 1).'.'.round($y, 1);
                                                }
                                            @endphp
                                            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" style="height: 80px" role="img" aria-label="Statistik akademik">
                                                <polyline points="{{ implode(' ', $titik) }}" fill="none" stroke="#206bc4" stroke-width="1.5"></polyline>
                                                @foreach($titik as $t)
                                                    @php [$cx, $cy] = explode('.', $t); @endphp
                                                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="1.4" fill="#206bc4"></circle>
                                                @endforeach
                                            </svg>
                                            <div class="d-flex justify-content-between text-secondary small">
                                                @foreach($labs as $l)
                                                    <span>{{ $l }}</span>
                                                @endforeach
                                            </div>
                                        </div></div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="card h-100"><div class="card-body p-2 text-center">
                                            <h4 class="card-title text-start">Status Pembayaran</h4>
                                            @php
                                                $lp = (int) ($stats['lunas_pct'] ?? 0);
                                                $r = 15.9; $c = 2 * pi() * $r;
                                            @endphp
                                            <svg viewBox="0 0 42 42" style="height: 90px" role="img" aria-label="Lunas {{ $lp }}%">
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#e6e8e9" stroke-width="5"></circle>
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#206bc4" stroke-width="5" stroke-dasharray="{{ round($c * $lp / 100, 1) }} {{ round($c, 1) }}" stroke-linecap="round" transform="rotate(-90 21 21)"></circle>
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#2fb344" stroke-width="5" stroke-dasharray="{{ round($c * min($lp, 100) / 100, 1) }} {{ round($c, 1) }}" stroke-linecap="round" transform="rotate(-90 21 21)" opacity="0.35"></circle>
                                            </svg>
                                            <div class="small text-start"><span class="badge badge-dot bg-blue"></span> Lunas {{ $lp }}%</div>
                                            <div class="small text-start"><span class="badge badge-dot bg-yellow"></span> Belum {{ 100 - $lp }}%</div>
                                        </div></div>
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

{{-- ============ STRIP FITUR ============ --}}
<div class="container-xl mt-4" style="max-width: 1280px" id="fitur">
    <div class="card"><div class="card-body">
        <div class="row text-center">
            @php
                $strip = [
                    ['🎓', 'PMB Online', 'Pendaftaran mahasiswa lebih mudah'],
                    ['👥', 'Manajemen Mahasiswa', 'Data lengkap & terintegrasi'],
                    ['📘', 'Akademik', 'KRS, KHS dan kurikulum'],
                    ['💳', 'Keuangan', 'Tagihan & pembayaran terintegrasi'],
                    ['📊', 'Laporan Lengkap', 'Data real-time & akurat'],
                    ['🛡', 'Keamanan Data', 'Berstandar enterprise'],
                ];
            @endphp
            @foreach($strip as [$ikon, $judul, $sub])
                <div class="col-6 col-md-2 mb-3 mb-md-0">
                    <div class="avatar bg-blue-lt mb-1">{{ $ikon }}</div>
                    <div><strong class="small">{{ $judul }}</strong></div>
                    <div class="text-secondary small">{{ $sub }}</div>
                </div>
            @endforeach
        </div>
    </div></div>
</div>

{{-- ============ MODUL ============ --}}
<div class="container-xl mt-4" style="max-width: 1280px" id="modul">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <div class="text-info small mb-1">Modul Lengkap</div>
            <h2>Fitur Unggulan Kampus ERP</h2>
            <p class="text-secondary mb-0">Semua kebutuhan kampus dalam satu platform terintegrasi</p>
        </div>
        <a href="{{ route('login') }}" class="d-none d-md-block">Lihat Semua Fitur →</a>
    </div>
    @php
        $moduls = [
            ['🧑‍🎓', 'Penerimaan Mahasiswa Baru (PMB)', 'pmb.create'],
            ['👥', 'Manajemen Mahasiswa', 'publik.prodi'],
            ['📖', 'Akademik & Kurikulum', 'admin.master'],
            ['📝', 'KRS & KHS', 'mahasiswa.krs.pengajuan'],
            ['💳', 'Keuangan & UKT', 'keuangan.bayar'],
            ['📚', 'LMS, Absensi & Bimbingan', 'lms.materi'],
            ['📜', 'Surat & Transkrip (QR)', 'surat.pengajuan'],
            ['📊', 'Pelaporan Akademik', 'dashboard.pimpinan'],
            ['📅', 'Kalender Akademik', 'kalender.index'],
            ['🤖', 'AI Chatbot & Analitik', 'ai.chat'],
            ['🎓', 'Alumni & Magang Hub', 'publik.alumni'],
            ['📈', 'Funnel PMB & Yudisium', 'pmb.funnel'],
        ];
    @endphp
    <div class="row row-cards">
        @foreach($moduls as [$ikon, $nama, $rute])
            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
                    <span class="avatar avatar-lg bg-blue-lt">{{ $ikon }}</span>
                    <div><h3 class="card-title mb-0">{{ $nama }}</h3><a href="{{ route($rute) }}" class="small">Buka →</a></div>
                </div></div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ PERAN ============ --}}
<div class="bg-light mt-4" id="peran">
    <div class="container-xl py-5" style="max-width: 1280px">
        <div class="row align-items-center">
            <div class="col-lg-3">
                <div class="text-info small mb-1">● Untuk Semua Peran</div>
                <h2>Akses Sesuai Peran dan Kewenangan</h2>
                <p class="text-secondary">Sistem yang fleksibel dengan hak akses terstruktur untuk setiap pengguna.</p>
                <a href="{{ route('dokumen.index') }}" class="btn btn-primary">Pelajari Selengkapnya →</a>
            </div>
            <div class="col-lg-9">
                <div class="row row-cards">
                    @php
                        $perans = [['Rektor', 'Monitoring strategis'], ['BAAK', 'Administrasi akademik'], ['Keuangan', 'Pengelolaan keuangan'], ['Dosen', 'Mengajar & menilai'], ['Mahasiswa', 'Akses layanan akademik']];
                    @endphp
                    @foreach($perans as [$nama, $deskripsi])
                        <div class="col">
                            <div class="card h-100"><div class="card-body text-center">
                                @php $avatars = ['Rektor' => 'avatar-rektor.png', 'BAAK' => 'avatar-baak.png', 'Keuangan' => 'avatar-keuangan.png', 'Dosen' => 'avatar-dosen.png', 'Mahasiswa' => 'avatar-mahasiswa.png']; @endphp
                                <span class="avatar avatar-lg mb-2" style="background-image: url('{{ asset('img/' . ($avatars[$nama] ?? 'avatar-mahasiswa.png')) }}'); background-size: cover"></span>
                                <div><strong>{{ $nama }}</strong></div>
                                <div class="text-secondary small">{{ $deskripsi }}</div>
                            </div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ ALUR KERJA ============ --}}
<div class="container-xl mt-4" style="max-width: 1280px">
    <div class="row align-items-center">
        <div class="col-lg-4">
            <span class="badge bg-blue-lt text-primary mb-2">Alur Kerja</span>
            <h2>Untuk Mendukung Siklus Akademik</h2>
            <p class="text-secondary">Dari pendaftaran hingga wisuda, semua lebih terstruktur.</p>
        </div>
        <div class="col-lg-8">
            <div class="row text-center">
                @php
                    $langkah = [['1', 'PMB', 'Pendaftaran Online'], ['2', 'Akademik', 'KRS & Perkuliahan'], ['3', 'Keuangan', 'Pembayaran & Tagihan'], ['4', 'Wisuda', 'Kelulusan']];
                @endphp
                @foreach($langkah as $i => [$no, $judul, $sub])
                    <div class="col-3">
                        <span class="avatar avatar-lg bg-primary text-white mb-2">{{ $no }}</span>
                        <div><strong>{{ $judul }}</strong></div>
                        <div class="text-secondary small">{{ $sub }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ============ KEAMANAN + FAQ ============ --}}
<div class="container-xl mt-4" style="max-width: 1280px" id="tentang">
    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card h-100 bg-dark text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                        <span class="badge bg-dark-lt text-info mb-2">● Mengapa Memilih Kampus ERP?</span>
                    <h2 class="text-white">Lebih Efisien, Lebih Profesional</h2>
                    <p class="text-white-50">Tingkatkan kualitas layanan kampus dengan sistem ERP yang handal, fleksibel dan mudah digunakan.</p>
                    <ul class="list-unstyled">
                        <li class="mb-2">✓ Integrasi penuh antar modul</li>
                        <li class="mb-2">✓ Keamanan tingkat tinggi</li>
                        <li class="mb-2">✓ Support &amp; update berkala</li>
                        <li class="mb-2">✓ Siap untuk pengembangan lebih lanjut</li>
                    </ul>
                    <a href="{{ route('dokumen.index') }}" class="btn btn-outline-light">Tentang Kami →</a>
                        </div>
                        <div class="col-md-5 text-center">
                            <img src="{{ asset('img/security-shield.png') }}" alt="Keamanan data kampus" class="img-fluid rounded" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6" id="faq">
            <span class="badge bg-blue-lt text-primary mb-2">FAQ</span>
            <h2>Pertanyaan yang Sering Diajukan</h2>
            <p class="text-secondary">Temukan jawaban atas pertanyaan seputar Kampus ERP.</p>
            <div class="accordion" id="faqAccordion">
                @php
                    $faqs = [
                        ['Apakah Kampus ERP mendukung multi-campus?', 'Struktur Fakultas → Prodi mendukung banyak program studi dalam satu instalasi. Multi-kampus penuh masuk roadmap.'],
                        ['Bagaimana cara mendapat demo?', 'Klik Demo Gratis dan masuk dengan akun demo yang tersedia di halaman login.'],
                        ['Apakah ada layanan support?', 'Ya — support & update berkala termasuk dalam paket implementasi.'],
                        ['Berapa biaya implementasi?', 'Tergantung jumlah modul dan skala kampus. Hubungi tim kami untuk penawaran.'],
                    ];
                @endphp
                @foreach($faqs as $i => [$tanya, $jawab])
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">+ {{ $tanya }}</button>
                        </h2>
                        <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary">{{ $jawab }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ============ CTA ============ --}}
<div class="bg-primary text-white mt-4">
    <div class="container-xl py-4" style="max-width: 1280px">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="text-white">✈ Siap Meningkatkan Pengelolaan Kampus Anda?</h2>
                <p class="text-white-50 mb-0">Mulai sekarang dan rasakan kemudahan mengelola kampus dengan Kampus ERP.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('login') }}" class="btn btn-light btn-lg">Mulai Demo Gratis →</a>
            </div>
        </div>
    </div>
</div>

{{-- ============ FOOTER ============ --}}
<footer class="container-xl mt-3 mb-3" style="max-width: 1280px" id="kontak">
    <div class="d-flex flex-wrap justify-content-between align-items-center text-secondary small">
        <span><strong class="text-dark">🎓 Kampus</strong> <span class="text-secondary">University ERP</span></span>
        <span>
            <a href="{{ route('home') }}" class="me-3">Beranda</a>
            <a href="#fitur" class="me-3">Fitur</a>
            <a href="#modul" class="me-3">Modul</a>
            <a href="{{ route('dokumen.index') }}" class="me-3">Dokumentasi</a>
            <a href="#tentang" class="me-3">Tentang</a>
            <a href="#kontak">Kontak</a>
        </span>
        <span>© {{ date('Y') }} Kampus ERP. Semua hak dilindungi.</span>
    </div>
</footer>
@endsection
