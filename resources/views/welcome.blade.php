@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', 'KAMPUS — University ERP & Academic Management System')

@push('styles')
<style>
    html { scroll-behavior: smooth; }
    .lp-hero {
        background:
            radial-gradient(900px 420px at 85% -5%, rgba(23,105,245,.14), transparent 60%),
            radial-gradient(700px 380px at 5% 10%, rgba(18,200,232,.12), transparent 60%),
            linear-gradient(180deg, #F2F6FD 0%, #FFFFFF 100%);
        overflow: hidden;
    }
    .lp-kicker {
        display: inline-flex; align-items: center; gap: .5rem;
        background: #E8F1FE; color: #1046A0;
        border-radius: 999px; padding: .3rem .9rem;
        font-size: .78rem; font-weight: 600;
    }
    .lp-kicker .dot { width: 8px; height: 8px; border-radius: 50%; background: #16A36A; }
    .lp-h1 { color: #101D35; font-weight: 800; letter-spacing: -.02em; }
    .lp-h1 .hl { color: #1769F5; position: relative; white-space: nowrap; }
    .lp-h1 .hl svg { position: absolute; left: 0; right: 0; bottom: -8px; width: 100%; height: 10px; }
    .lp-check { display: inline-flex; align-items: center; gap: .4rem; color: #33475F; font-size: .9rem; }
    .lp-check .tick {
        width: 20px; height: 20px; border-radius: 50%;
        background: #E5F4EC; color: #16A36A;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 800; flex-shrink: 0;
    }
    .lp-mock {
        background: #fff; border-radius: 20px;
        box-shadow: 0 30px 70px rgba(16,35,75,.18);
        border: 1px solid #E6EDF7; overflow: hidden;
    }
    .lp-mock-top {
        display: flex; align-items: center; gap: .6rem;
        padding: .7rem 1rem; border-bottom: 1px solid #EDF1F7;
    }
    .lp-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(135deg, #1769F5, #12C8E8);
        color: #fff; font-size: .7rem; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .lp-tile {
        background: #F6F9FE; border: 1px solid #E8EEF7; border-radius: 12px;
        padding: .6rem .7rem; min-width: 0;
    }
    .lp-tile b { display: block; color: #101D35; font-size: 1.05rem; line-height: 1.1; }
    .lp-tile small { color: #77849A; font-size: .7rem; }
    .lp-tile .ico {
        width: 30px; height: 30px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: .35rem;
    }
    .lp-qa a {
        display: flex; align-items: center; gap: .6rem;
        padding: .5rem .6rem; border-radius: 10px; color: #172B4D;
        text-decoration: none; font-size: .85rem;
    }
    .lp-qa a:hover { background: #F2F6FD; }
    .lp-qa .qi {
        width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; color: #fff;
    }
    .lp-photo-band { border-radius: 22px; overflow: hidden; position: relative; }
    .lp-photo-band img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .lp-trust {
        background: #fff; border: 1px solid #E8EEF7; border-radius: 18px;
        box-shadow: 0 12px 30px rgba(16,35,75,.07);
    }
    .lp-pill {
        display: inline-flex; align-items: center; gap: .5rem;
        border: 1px solid #E4EAF4; border-radius: 999px;
        padding: .45rem .95rem; font-size: .85rem; color: #33475F; background: #fff;
        white-space: nowrap;
    }
    .lp-pill .mono {
        width: 26px; height: 26px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: .7rem; font-weight: 800; flex-shrink: 0;
    }
    .lp-mini {
        background: #fff; border: 1px solid #E8EEF7; border-radius: 16px;
        padding: 1rem .6rem; text-align: center; height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
        color: #172B4D; text-decoration: none; display: block;
    }
    .lp-mini:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(23,105,245,.13); }
    .lp-mini .mi {
        width: 46px; height: 46px; border-radius: 14px; margin: 0 auto .55rem;
        display: flex; align-items: center; justify-content: center; color: #fff;
    }
    .lp-mini b { display: block; font-size: .85rem; }
    .lp-mini small { color: #77849A; font-size: .72rem; }
    .lp-flow-num {
        width: 52px; height: 52px; border-radius: 50%;
        background: #E8F1FE; color: #1769F5;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: .5rem;
    }
    .lp-flow-line { position: absolute; top: 26px; left: calc(50% + 34px); width: calc(100% - 68px); height: 2px; background: #D8E4F5; }
    .lp-band {
        background: linear-gradient(120deg, #0F2247 0%, #1046A0 60%, #1769F5 100%);
        border-radius: 18px; color: #fff;
    }
    .lp-feat {
        background: #fff; border: 1px solid #E8EEF7; border-radius: 16px;
        padding: 1.1rem; height: 100%;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .lp-feat:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(23,105,245,.13); }
    .lp-feat .fi {
        width: 46px; height: 46px; border-radius: 14px; margin-bottom: .7rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .lp-stats { background: linear-gradient(120deg, #0F2247 0%, #13408F 55%, #1769F5 100%); border-radius: 22px; }
    .lp-stat-num { font-size: 1.9rem; font-weight: 800; }
    .lp-testi { background: #fff; border: 1px solid #E8EEF7; border-radius: 16px; padding: 1.25rem; height: 100%; }
    .lp-testi img { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; }
    .lp-stars { color: #F5A623; letter-spacing: 2px; }
    .lp-cta {
        border-radius: 24px; overflow: hidden; position: relative;
        background: linear-gradient(120deg, rgba(15,34,71,.96), rgba(23,105,245,.88));
    }
    .lp-cta > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .28; }
    .lp-footer { background: #0C1930; border-radius: 24px 24px 0 0; }
    .lp-footer a { color: #9DB1D1; text-decoration: none; }
    .lp-footer a:hover { color: #fff; }
    #toTop {
        position: fixed; right: 22px; bottom: 22px; z-index: 1030;
        width: 44px; height: 44px; border-radius: 50%; border: 0;
        background: #1769F5; color: #fff; font-size: 1.2rem;
        display: none; align-items: center; justify-content: center;
        box-shadow: 0 10px 26px rgba(23,105,245,.4);
    }
    @media (max-width: 991.98px) { .lp-flow-line { display: none; } }
</style>
@endpush

@section('content')
@php
    $jmlMhs = number_format($stats['mahasiswa'] ?? 0);
    $jmlDosen = number_format($stats['dosen'] ?? 0);
    $jmlProdi = number_format($stats['prodi'] ?? 0);
    $krsPct = (int) ($stats['krs_pct'] ?? 0);
    $lunasPct = (int) ($stats['lunas_pct'] ?? 0);
    $vals = $stats['bayar_bulanan']['nominal'] ?? [];
    $labels = $stats['bayar_bulanan']['labels'] ?? [];
    $maks = max(1, max($vals ?: [0]));
    $pts = [];
    $n = max(1, count($vals));
    foreach ($vals as $i => $v) {
        $x = 8 + ($i * (284 / max(1, $n - 1 ?: 1)));
        $y = 82 - max(6, $v / $maks * 70);
        $pts[] = round($x, 1).','.round($y, 1);
    }
    $poly = implode(' ', $pts);
@endphp

{{-- ============ HERO ============ --}}
<div class="lp-hero">
    <div class="container-xl py-5" style="max-width:1280px">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="lp-kicker"><span class="dot"></span> Sistem Informasi Kampus Terpadu</span>
                <h1 class="lp-h1 display-5 mt-3">Satu Platform Terintegrasi untuk Seluruh Operasi
                    <span class="hl">Kampus<svg viewBox="0 0 120 10" preserveAspectRatio="none"><path d="M2 8 Q 60 1 118 7" stroke="#1769F5" stroke-width="4" fill="none" stroke-linecap="round"/></svg></span>
                </h1>
                <p class="text-secondary mt-3">KAMPUS adalah Academic ERP modern untuk mengelola seluruh proses perguruan tinggi secara terintegrasi, mulai dari PMB, akademik, keuangan, hingga alumni — dipakai {{ $jmlMhs }} mahasiswa dan {{ $jmlDosen }} dosen yang terdata.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">▶ Lihat Demo Live</a>
                    <a href="{{ route('pmb.create') }}" class="btn btn-outline-primary btn-lg">Request Demo →</a>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <span class="lp-check"><span class="tick">✓</span> Solusi Lengkap</span>
                    <span class="lp-check"><span class="tick">✓</span> Mudah Digunakan</span>
                    <span class="lp-check"><span class="tick">✓</span> Aman &amp; Andal</span>
                </div>
            </div>
            <div class="col-lg-6">
                {{-- Mockup dashboard (HTML/CSS + angka real agregat) --}}
                <div class="lp-mock" role="img" aria-label="Pratinjau dashboard KAMPUS ERP">
                    <div class="lp-mock-top">
                        <img src="{{ asset('img/logo-kampus-mark.png') }}" alt="" width="26" height="26" style="border-radius:7px">
                        <strong class="small">KAMPUS</strong>
                        <span class="text-secondary small d-none d-sm-inline">· Ringkasan Eksekutif</span>
                        <span class="ms-auto d-flex align-items-center gap-2">
                            <span class="badge bg-green-lt">● Live</span>
                            <span class="lp-avatar">AD</span>
                        </span>
                    </div>
                    <div class="p-3" style="background:#F6F9FE">
                        <div class="row g-2 mb-2">
                            <div class="col-4"><div class="lp-tile"><span class="ico" style="background:#E3EDFD;color:#1769F5">◉</span><b>{{ $jmlMhs }}</b><small>Mahasiswa</small></div></div>
                            <div class="col-4"><div class="lp-tile"><span class="ico" style="background:#FFF4E0;color:#E9A23B">◉</span><b>{{ $jmlDosen }}</b><small>Dosen</small></div></div>
                            <div class="col-4"><div class="lp-tile"><span class="ico" style="background:#E5F4EC;color:#16A36A">◉</span><b>{{ $jmlProdi }}</b><small>Prodi</small></div></div>
                        </div>
                        <div class="bg-white border rounded-3 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-secondary fw-bold">Statistik Akademik</small>
                                <small class="text-secondary">{{ $krsPct }}% KRS disetujui</small>
                            </div>
                            <svg viewBox="0 0 300 90" class="w-100" style="height:90px" preserveAspectRatio="none" aria-hidden="true">
                                <defs><linearGradient id="lpg" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#1769F5" stop-opacity=".35"/>
                                    <stop offset="1" stop-color="#1769F5" stop-opacity="0"/>
                                </linearGradient></defs>
                                <polygon points="8,88 {{ $poly }} 292,88" fill="url(#lpg)"/>
                                <polyline points="{{ $poly }}" fill="none" stroke="#1769F5" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                            </svg>
                            <div class="d-flex justify-content-between text-secondary" style="font-size:.62rem">
                                @foreach($labels as $lb)<span>{{ $lb }}</span>@endforeach
                            </div>
                        </div>
                        <div class="bg-white border rounded-3 p-2 lp-qa">
                            <small class="text-secondary fw-bold d-block px-1 mb-1">Quick Access</small>
                            <a href="{{ route('mahasiswa.krs.pengajuan') }}"><span class="qi" style="background:#1769F5">▤</span> KRS Online</a>
                            <a href="{{ route('keuangan.bayar') }}"><span class="qi" style="background:#E9A23B">▣</span> Pembayaran</a>
                            <a href="{{ route('dashboard.pimpinan') }}"><span class="qi" style="background:#7C6FF0">◫</span> Laporan</a>
                            <a href="{{ route('publik.pengumuman') }}"><span class="qi" style="background:#12C8E8">✉</span> Pengumuman</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Foto kampus --}}
        <div class="lp-photo-band mt-4" style="height:min(46vw,380px)">
            <img src="{{ asset('img/kampus-hero-wan.png') }}" alt="Gedung kampus modern" loading="lazy">
            <div class="position-absolute bottom-0 start-0 end-0 p-3 p-md-4 d-flex flex-wrap align-items-end gap-2" style="background:linear-gradient(transparent, rgba(12,25,48,.75))">
                <div class="text-white me-auto">
                    <strong class="d-block">Kampus Modern, Sistem Modern</strong>
                    <span class="small opacity-75">Gedung, layanan, dan data akademik dalam satu ekosistem digital.</span>
                </div>
                <a href="{{ route('pmb.create') }}" class="btn btn-light">Daftar Sekarang →</a>
            </div>
        </div>
        {{-- Kepercayaan --}}
        <div class="lp-trust mt-3 px-3 px-md-4 py-3 d-flex flex-wrap align-items-center gap-2">
            <div class="me-2">
                <strong class="d-block small">Dipercaya oleh</strong>
                <strong class="d-block">Ratusan Perguruan Tinggi</strong>
            </div>
            <span class="lp-pill"><span class="mono" style="background:#1769F5">PT</span> Perguruan Tinggi Negeri</span>
            <span class="lp-pill"><span class="mono" style="background:#7C6FF0">PS</span> Perguruan Tinggi Swasta</span>
            <span class="lp-pill"><span class="mono" style="background:#12C8E8">PO</span> Politeknik &amp; Vokasi</span>
            <span class="lp-pill"><span class="mono" style="background:#16A36A">IN</span> Institut &amp; Sekolah Tinggi</span>
            <span class="text-secondary small ms-auto">dan lainnya…</span>
        </div>
        {{-- 8 modul ringkas --}}
        <div class="row g-2 mt-3">
            @php
                $minis = [
                    ['PMB &<br>Penerimaan', 'pmb.create', '#1769F5', 'M3 12l9-7 9 7M5 10v10h5v-6h4v6h5V10'],
                    ['Akademik &<br>Kurikulum', 'publik.prodi', '#12C8E8', 'M4 5h16v12H4zM4 9h16M8 5V3h8v2'],
                    ['LMS &<br>CBT', 'lms.materi', '#7C6FF0', 'M4 5h16v10H4zM10 9l4 3-4 3z'],
                    ['Keuangan &<br>Akuntansi', 'keuangan.bayar', '#E9A23B', 'M4 7h16v10H4zM4 10h16M7 14h4'],
                    ['HR &<br>Payroll', 'keuangan.payroll', '#16A36A', 'M9 11a3 3 0 106 0 3 3 0 00-6 0M3 20c0-3 4-5 9-5s9 2 9 5'],
                    ['Penelitian &<br>Pengabdian', 'riset.publikasi', '#E9658B', 'M9 3h6v6l-3 3-3-3zM12 12v9M8 21h8'],
                    ['Alumni &<br>Tracer Study', 'publik.alumni', '#0EA5E9', 'M8 11a3 3 0 106 0 3 3 0 00-6 0M2 20c0-3 3-5 7-5h2M16 4l5 5m0-5l-5 5'],
                    ['Integrasi &<br>PDDikti', 'admin.integrasi', '#64748B', 'M9 15l-4-4 4-4M15 9l4 4-4 4M13 5l-2 14'],
                ];
            @endphp
            @foreach($minis as [$nama, $rute, $warna, $path])
                <div class="col-6 col-md-3">
                    <a class="lp-mini" href="{{ route($rute) }}">
                        <span class="mi" style="background:{{ $warna }}">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                        </span>
                        <b>{!! $nama !!}</b>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ============ MENGAPA + ALUR ============ --}}
<div class="container-xl mt-5" style="max-width:1280px" id="solusi">
    <div class="row g-4">
        <div class="col-lg-6">
            <span class="lp-kicker"><span class="dot"></span> Mengapa KAMPUS?</span>
            <h2 class="fw-bold mt-2">Solusi ERP Kampus untuk Masa Depan Pendidikan</h2>
            <p class="text-secondary">Dengan teknologi terkini dan didukung tim profesional, KAMPUS membantu institusi Anda meningkatkan efisiensi, transparansi, dan kualitas layanan.</p>
            <ul class="list-unstyled mt-3">
                @foreach(['Integrasi penuh antar modul', 'Arsitektur siap multi-kampus', 'Keamanan data kelas enterprise', 'Dukungan teknis profesional'] as $c)
                    <li class="mb-2 lp-check"><span class="tick">✓</span> {{ $c }}</li>
                @endforeach
            </ul>
            <a href="{{ route('dokumen.index') }}" class="btn btn-primary mt-2">Pelajari Lebih Lanjut →</a>
        </div>
        <div class="col-lg-6">
            <div class="text-center text-primary small fw-bold mb-3">Alur Lengkap dari Pendaftaran hingga Alumni</div>
            <div class="row text-center g-2">
                @php
                    $alurs = [['PMB', 'Pendaftaran Online'], ['Akademik', 'KRS & Perkuliahan'], ['Keuangan', 'Pembayaran & Laporan'], ['Wisuda', '& Alumni']];
                @endphp
                @foreach($alurs as $i => [$judul, $sub])
                    <div class="col-3 position-relative">
                        @if(!$loop->last)<span class="lp-flow-line d-none d-md-block"></span>@endif
                        <span class="lp-flow-num fw-bold">{{ $i + 1 }}</span>
                        <div><strong class="small">{{ $judul }}</strong></div>
                        <div class="text-secondary" style="font-size:.72rem">{{ $sub }}</div>
                    </div>
                @endforeach
            </div>
            <div class="lp-band mt-4 px-3 py-2 d-flex align-items-center justify-content-center gap-2 small">
                <span class="tick" style="width:20px;height:20px;border-radius:50%;background:#16A36A;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800">✓</span>
                Satu Sistem, Satu Data, Satu Ekosistem Kampus
            </div>
        </div>
    </div>
</div>

{{-- ============ FITUR LENGKAP ============ --}}
<div class="container-xl mt-5" style="max-width:1280px" id="fitur">
    <span class="lp-kicker"><span class="dot"></span> Modul Unggulan</span>
    <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mt-2 mb-3">
        <h2 class="fw-bold mb-0">Fitur Lengkap untuk Semua Kebutuhan Kampus</h2>
        <a href="{{ route('dokumen.index') }}" class="small">Lihat Semua Fitur →</a>
    </div>
    @php
        $fiturs = [
            ['PMB Online', 'Pendaftaran dan seleksi calon mahasiswa.', 'pmb.create', '#1769F5', 'M3 12l9-7 9 7M5 10v10h5v-6h4v6h5V10'],
            ['Akademik', 'KRS, KHS, dan manajemen perkuliahan.', 'mahasiswa.krs.pengajuan', '#12C8E8', 'M4 5h16v12H4zM4 9h16M8 5V3h8v2'],
            ['LMS & CBT', 'E-learning dan ujian online.', 'lms.materi', '#7C6FF0', 'M4 5h16v10H4zM10 9l4 3-4 3z'],
            ['Keuangan', 'Manajemen keuangan terintegrasi.', 'keuangan.bayar', '#E9A23B', 'M4 7h16v10H4zM4 10h16M7 14h4'],
            ['HR & Payroll', 'Data SDM dan penggajian.', 'keuangan.payroll', '#16A36A', 'M9 11a3 3 0 106 0 3 3 0 00-6 0M3 20c0-3 4-5 9-5s9 2 9 5'],
            ['Penelitian', 'Penelitian dan pengabdian.', 'riset.publikasi', '#E9658B', 'M9 3h6v6l-3 3-3-3zM12 12v9M8 21h8'],
            ['Skripsi & Tugas Akhir', 'Bimbingan dan sidang.', 'skripsi.pengajuan', '#7C6FF0', 'M6 3h9l4 4v14H6zM9 12h7M9 16h5'],
            ['Alumni', 'Tracer Study dan jaringan.', 'publik.alumni', '#0EA5E9', 'M8 11a3 3 0 106 0 3 3 0 00-6 0M2 20c0-3 3-5 7-5h2M16 4l5 5m0-5l-5 5'],
            ['Aset', 'Manajemen aset kampus.', 'keuangan.hutang', '#F59E0B', 'M4 8l8-4 8 4v8l-8 4-8-4zM4 8l8 4 8-4M12 12v8'],
            ['Integrasi PDDikti', 'Sinkronisasi data nasional.', 'pddikti.index', '#64748B', 'M9 15l-4-4 4-4M15 9l4 4-4 4M13 5l-2 14'],
        ];
    @endphp
    <div class="row g-3" id="modul">
        @foreach($fiturs as [$nama, $deskripsi, $rute, $warna, $path])
            <div class="col-md-6 col-lg-4">
                <a href="{{ route($rute) }}" class="text-decoration-none text-dark">
                    <div class="lp-feat">
                        <span class="fi" style="background:{{ $warna }}22;color:{{ $warna }}">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                        </span>
                        <h3 class="h5 mb-1">{{ $nama }}</h3>
                        <p class="text-secondary small mb-0">{{ $deskripsi }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ STATISTIK ============ --}}
<div class="container-xl mt-5" style="max-width:1280px">
    <div class="lp-stats text-white px-4 py-4">
        <div class="row g-3 align-items-center text-center">
            <div class="col-md-3">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <span style="font-size:2rem">🏆</span>
                    <div class="text-start"><strong class="d-block">Terbukti &amp; Terpercaya</strong><small class="opacity-75">Data langsung dari database</small></div>
                </div>
            </div>
            <div class="col-6 col-md-3"><div class="lp-stat-num">{{ $jmlMhs }}+</div><small class="opacity-75">Mahasiswa Terdata</small></div>
            <div class="col-6 col-md-3"><div class="lp-stat-num">{{ $jmlDosen }}+</div><small class="opacity-75">Dosen Aktif</small></div>
            <div class="col-6 col-md-3"><div class="lp-stat-num">{{ $lunasPct }}%</div><small class="opacity-75">Tingkat Pelunasan</small></div>
        </div>
    </div>
</div>

{{-- ============ TESTIMONI ============ --}}
<div class="container-xl mt-5" style="max-width:1280px">
    <span class="lp-kicker"><span class="dot"></span> Testimoni</span>
    <h2 class="fw-bold mt-2">Apa Kata Mereka?</h2>
    <p class="text-secondary small">Kepuasan pengguna adalah prioritas kami. <span class="fst-italic">(kutipan ilustrasi)</span></p>
    <div class="row g-3 mt-1">
        @php
            $testis = [
                ['img/avatar-dosen.png', 'Dr. Siti Rahayu, M.Pd', 'Wakil Rektor Akademik', '“KAMPUS sangat membantu transformasi digital kampus kami.”'],
                ['img/avatar-rektor.png', 'Prof. Budi Santoso, M.Kom', 'Dekan Fakultas Teknik', '“Integrasinya lengkap dan dukungannya luar biasa.”'],
                ['img/avatar-baak.png', 'Dr. Andi Wijaya', 'Kepala BAAK', '“Satu sistem untuk semua kebutuhan kampus.”'],
            ];
        @endphp
        @foreach($testis as [$foto, $nama, $jabatan, $kutip])
            <div class="col-md-4">
                <div class="lp-testi">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="{{ asset($foto) }}" alt="{{ $nama }}" loading="lazy">
                        <div><strong class="d-block small">{{ $nama }}</strong><small class="text-secondary">{{ $jabatan }}</small></div>
                    </div>
                    <p class="text-secondary small">{{ $kutip }}</p>
                    <div class="lp-stars">★★★★★</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ CTA ============ --}}
<div class="container-xl mt-5" style="max-width:1280px">
    <div class="lp-cta p-4 p-md-5 text-white">
        <img src="{{ asset('img/kampus-hero-wan.png') }}" alt="" aria-hidden="true" loading="lazy">
        <div class="position-relative text-center" style="z-index:1">
            <h2 class="fw-bold">Siap Meningkatkan Layanan Kampus Anda?</h2>
            <p class="opacity-75">Hubungi tim kami untuk demo, konsultasi, dan informasi lebih lanjut.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="{{ route('login') }}" class="btn btn-light btn-lg">Request Demo →</a>
                <a href="{{ route('pmb.create') }}" class="btn btn-outline-light btn-lg">⌕ Mulai PMB</a>
            </div>
        </div>
    </div>
</div>

{{-- ============ FOOTER ============ --}}
<div class="container-xl mt-4" style="max-width:1280px" id="kontak">
    <footer class="lp-footer text-white-50 px-4 pt-4 pb-3">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="{{ asset('img/logo-kampus-mark.png') }}" alt="Logo KAMPUS" width="32" height="32" style="border-radius:9px">
                    <div><strong class="text-white d-block">KAMPUS ERP</strong><small>Smart Campus, Better Future</small></div>
                </div>
                <p class="small">Academic ERP terintegrasi: PMB, akademik, keuangan, dan pelaporan dalam satu sistem.</p>
            </div>
            <div class="col-6 col-md-2">
                <strong class="text-white d-block small mb-2">Navigasi</strong>
                <div class="d-grid gap-1 small">
                    <a href="{{ route('home') }}">Beranda</a>
                    <a href="{{ url('/#fitur') }}">Fitur</a>
                    <a href="{{ url('/#modul') }}">Modul</a>
                    <a href="{{ route('dokumen.index') }}">Dokumentasi</a>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <strong class="text-white d-block small mb-2">Layanan</strong>
                <div class="d-grid gap-1 small">
                    <a href="{{ route('pmb.create') }}">Pendaftaran PMB</a>
                    <a href="{{ route('publik.prodi') }}">Program Studi</a>
                    <a href="{{ route('publik.pengumuman') }}">Pengumuman</a>
                    <a href="{{ route('publik.karier') }}">Karier</a>
                </div>
            </div>
            <div class="col-md-3">
                <strong class="text-white d-block small mb-2">Bantuan</strong>
                <div class="d-grid gap-1 small">
                    <a href="{{ route('dokumen.index') }}">Dokumentasi</a>
                    <a href="{{ url('/#faq') }}">FAQ</a>
                    <a href="{{ route('health') }}">Status Sistem</a>
                </div>
            </div>
        </div>
        <hr class="border-secondary opacity-25">
        <div class="d-flex flex-wrap justify-content-between gap-2 small">
            <span>© {{ date('Y') }} KAMPUS ERP. Angka di halaman ini dihitung langsung dari database.</span>
            <span><a href="{{ url('/#faq') }}" class="me-3">Syarat &amp; Ketentuan</a><a href="{{ url('/#faq') }}" class="me-3">Kebijakan Privasi</a><a href="{{ route('dokumen.index') }}">Bantuan</a></span>
        </div>
    </footer>
</div>

<button id="toTop" aria-label="Kembali ke atas">↑</button>
@endsection

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('toTop');
    window.addEventListener('scroll', function () {
        btn.style.display = window.scrollY > 600 ? 'inline-flex' : 'none';
    });
    btn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
})();
</script>
@endpush
