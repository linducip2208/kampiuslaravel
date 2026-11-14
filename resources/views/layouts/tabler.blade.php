<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KAMPUS — University ERP & Academic Management System: PMB, akademik, keuangan, dan pelaporan kampus terpadu.">
    <meta property="og:title" content="@yield('title', config('app.name', 'KAMPUS'))">
    <meta property="og:description" content="Kelola PMB, KRS, nilai, keuangan, dan laporan dalam satu sistem kampus terpadu.">
    <meta property="og:type" content="website">
    <title>@yield('title', config('app.name', 'KAMPUS'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('img/logo-kampus-mark.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#1769F5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <style>
        :root {
            --kampius-primary: #1769F5;
            --kampius-primary-dark: #1046A0;
            --kampius-navy: #101D35;
            --kampius-navy-2: #172B4D;
            --kampius-cyan: #12C8E8;
            --kampius-bg: #F5F7FB;
            --kampius-surface: #FFFFFF;
            --kampius-text: #18263D;
            --kampius-muted: #77849A;
            --kampius-border: #E6EBF3;
            --kampius-success: #16A36A;
            --kampius-warning: #E9A23B;
            --kampius-danger: #DC4545;
            --kampius-sidebar-w: 264px;
            --kampius-sidebar-w-collapsed: 76px;
        }
        html { scroll-behavior: smooth; }
        body { background: var(--kampius-bg); color: var(--kampius-text); }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid var(--kampius-primary);
            outline-offset: 2px;
        }
        /* ===== Sidebar ===== */
        .kampius-sidebar {
            width: var(--kampius-sidebar-w);
            background: var(--kampius-surface);
            border-right: 1px solid var(--kampius-border);
            position: fixed;
            top: 0; bottom: 0; left: 0;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            transition: width .2s ease, transform .25s ease;
        }
        .kampius-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid var(--kampius-border);
            min-height: 68px;
        }
        .kampius-brand-mark {
            width: 38px; height: 38px;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--kampius-primary), var(--kampius-primary-dark));
            color: #fff;
            font-weight: 800;
            font-size: 1.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kampius-brand-text { line-height: 1.15; white-space: nowrap; overflow: hidden; }
        .kampius-brand-text strong { display: block; letter-spacing: .04em; color: var(--kampius-navy); }
        .kampius-brand-text small { color: var(--kampius-muted); font-size: .7rem; }
        .kampius-collapse-btn {
            margin-left: auto;
            border: 1px solid var(--kampius-border);
            background: #fff;
            border-radius: 8px;
            width: 30px; height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--kampius-muted);
            flex-shrink: 0;
        }
        .kampius-collapse-btn:hover { color: var(--kampius-primary); border-color: var(--kampius-primary); }
        .kampius-nav { flex: 1; overflow-y: auto; padding: .75rem .7rem 1rem; }
        .kampius-nav-group {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--kampius-muted);
            padding: 1rem .7rem .35rem;
            white-space: nowrap;
        }
        .kampius-nav-link {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .55rem .7rem;
            border-radius: 10px;
            color: var(--kampius-navy-2);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            position: relative;
            white-space: nowrap;
        }
        .kampius-nav-link:hover { background: rgba(23,105,245,.08); color: var(--kampius-primary-dark); }
        .kampius-nav-link.active {
            background: rgba(23,105,245,.12);
            color: var(--kampius-primary-dark);
            font-weight: 700;
        }
        .kampius-nav-link.active::before {
            content: "";
            position: absolute;
            left: -0.7rem;
            top: 20%;
            bottom: 20%;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: var(--kampius-primary);
        }
        .kampius-nav-link svg { width: 20px; height: 20px; flex-shrink: 0; }
        .kampius-sidebar-footer {
            border-top: 1px solid var(--kampius-border);
            padding: .8rem 1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .kampius-user-chip { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .kampius-user-chip .meta { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* Collapsed (desktop) */
        @media (min-width: 992px) {
            body.kampius-collapsed .kampius-sidebar { width: var(--kampius-sidebar-w-collapsed); }
            body.kampius-collapsed .kampius-brand-text,
            body.kampius-collapsed .kampius-nav-group,
            body.kampius-collapsed .kampius-nav-link span.kampius-nav-label,
            body.kampius-collapsed .kampius-user-chip .meta,
            body.kampius-collapsed .kampius-collapse-btn .chev-open { display: none; }
            body.kampius-collapsed .kampius-collapse-btn .chev-closed { display: inline !important; }
            body.kampius-collapsed .kampius-collapse-btn .chev-open { display: none !important; }
            body.kampius-collapsed .page-wrapper { margin-left: var(--kampius-sidebar-w-collapsed) !important; }
            body.kampius-collapsed .kampius-nav-link { justify-content: center; }
            body.kampius-collapsed .kampius-brand { justify-content: center; padding: 1rem .5rem; }
            body.kampius-collapsed .kampius-collapse-btn { margin-left: 0; }
            body.kampius-collapsed .kampius-sidebar-footer { justify-content: center; }
        }
        .kampius-collapse-btn .chev-closed { display: none; }
        /* ===== Page offset ===== */
        @media (min-width: 992px) {
            .kampius-shell .page-wrapper { margin-left: var(--kampius-sidebar-w); }
        }
        @media (max-width: 991.98px) {
            .kampius-sidebar { transform: translateX(-100%); box-shadow: none; }
            body.kampius-nav-open .kampius-sidebar { transform: translateX(0); box-shadow: 0 0 60px rgba(16,29,53,.25); }
            .kampius-nav-overlay {
                position: fixed; inset: 0; z-index: 1029;
                background: rgba(16,29,53,.45);
                opacity: 0; pointer-events: none; transition: opacity .2s ease;
            }
            body.kampius-nav-open .kampius-nav-overlay { opacity: 1; pointer-events: auto; }
        }
        /* ===== Topbar ===== */
        .kampius-topbar {
            background: var(--kampius-surface);
            border-bottom: 1px solid var(--kampius-border);
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .kampius-search { max-width: 340px; }
        .kampius-period {
            font-size: .78rem;
            color: var(--kampius-navy-2);
            background: rgba(23,105,245,.1);
            border-radius: 999px;
            padding: .3rem .8rem;
            white-space: nowrap;
        }
        .kampius-notif-dot {
            position: absolute;
            top: 6px; right: 8px;
            min-width: 18px; height: 18px;
            border-radius: 999px;
            background: var(--kampius-danger);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }
        /* ===== Content cards shared ===== */
        .kampius-card { border: 1px solid var(--kampius-border); border-radius: 16px; background: var(--kampius-surface); }
        .kampius-muted { color: var(--kampius-muted); }
        .kampius-empty {
            border: 1px dashed var(--kampius-border);
            border-radius: 16px;
            background: var(--kampius-surface);
            text-align: center;
            padding: 2.5rem 1.5rem;
        }
        .kampius-empty-icon {
            width: 52px; height: 52px;
            border-radius: 16px;
            background: rgba(23,105,245,.1);
            color: var(--kampius-primary);
            font-size: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .75rem;
        }
        .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: .82rem; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition: none !important; animation: none !important; }
        }
    </style>
    <livewire:styles />
    @stack('styles')
</head>
<body>
<a href="#main-content" class="visually-hidden-focusable">Lewati ke konten utama</a>
<div class="page">
    @hasSection('public-nav')
        <header class="navbar navbar-expand-md d-print-none sticky-top bg-white border-bottom">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#public-menu" aria-label="Menu navigasi">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex align-items-center gap-2"><img src="{{ asset('img/logo-kampus-mark.png') }}" alt="Logo KAMPUS" width="30" height="30" style="border-radius:8px">KAMPUS <span class="text-secondary small fw-normal">ERP</span></a>
                </h1>
                <div class="collapse navbar-collapse" id="public-menu">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('publik.prodi*') ? 'active' : '' }}" href="{{ route('publik.prodi') }}">Program Studi</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('publik.pengumuman*') ? 'active' : '' }}" href="{{ route('publik.pengumuman') }}">Pengumuman</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('pmb.*') ? 'active' : '' }}" href="{{ route('pmb.create') }}">PMB</a></li>
                    </ul>
                    <div class="ms-auto d-flex gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">Masuk</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl" id="main-content" tabindex="-1">
                    @include('layouts._alerts')
                    @yield('content')
                </div>
            </div>
        </div>
    @else
        <div class="kampius-shell">
        <div class="kampius-nav-overlay" data-kampius-nav-close aria-hidden="true"></div>
        <aside class="kampius-sidebar" aria-label="Navigasi utama">
            <div class="kampius-brand">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none" aria-label="KAMPUS Dashboard">
                    <img src="{{ asset('img/logo-kampus-mark.png') }}" alt="Logo KAMPUS" width="38" height="38" style="border-radius:11px;flex-shrink:0">
                    <span class="kampius-brand-text"><strong>KAMPUS</strong><small>University ERP</small></span>
                </a>
                <button type="button" class="kampius-collapse-btn d-none d-lg-inline-flex" data-kampius-collapse aria-label="Lipatkan navigasi">
                    <svg class="chev-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    <svg class="chev-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>
            <nav class="kampius-nav">
                @php
                    $isDosenOnly = auth()->check() && auth()->user()->hasAnyRole(['dosen']) && ! auth()->user()->hasAnyRole(['mahasiswa']);
                    $krsRoute = $isDosenOnly ? 'dosen.krs.persetujuan' : 'mahasiswa.krs.pengajuan';
                    $keuanganRoute = auth()->check() && auth()->user()->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi']) ? 'keuangan.tagihan' : 'keuangan.bayar';
                    $suratRoute = auth()->check() && auth()->user()->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi']) ? 'surat.nomor' : 'surat.pengajuan';
                    $skripsiRoute = $isDosenOnly ? 'skripsi.pembimbing' : 'skripsi.pengajuan';
                    $menuGroups = [
                        'Overview' => [
                            ['route' => 'dashboard', 'label' => 'Ringkasan', 'roles' => null, 'icon' => 'M3 12l9-9 9 9M9 21V12h6v9', 'pattern' => 'dashboard'],
                            ['route' => 'dashboard.pimpinan', 'label' => 'Dashboard Pimpinan', 'roles' => ['pimpinan', 'superadmin'], 'icon' => 'M3 3v18h18M7 15l-4-4 3 3 5-6', 'pattern' => 'dashboard.pimpinan'],
                        ],
                        'Akademik' => [
                            ['route' => 'admin.pmb.verifikasi', 'label' => 'PMB', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M8 7V3m8 4V3M3 11h18', 'pattern' => 'admin.pmb*'],
                            ['route' => $krsRoute, 'label' => 'KRS', 'roles' => ['mahasiswa', 'dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0 2-2h-2', 'pattern' => 'mahasiswa.krs*|dosen.krs*|krs.*'],
                            ['route' => 'dosen.wali', 'label' => 'Perwalian', 'roles' => ['dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'pattern' => 'dosen.wali'],
                            ['route' => 'publik.prodi', 'label' => 'Program Studi', 'roles' => null, 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4', 'pattern' => 'publik.prodi*'],
                            ['route' => 'admin.kurikulum', 'label' => 'Kurikulum', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M4 4h16v12H4z', 'pattern' => 'admin.kurikulum|admin.master'],
                            ['route' => 'admin.jadwal', 'label' => 'Jadwal Kuliah', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M8 7V3m8 4V3M3 11h18', 'pattern' => 'admin.jadwal'],
                            ['route' => 'lms.materi', 'label' => 'LMS', 'roles' => ['dosen', 'mahasiswa', 'superadmin', 'admin_prodi'], 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 1 4 4.5v15z', 'pattern' => 'lms.materi|lms.tugas|lms.tugas.kumpul|absensi.*'],
                        ['route' => 'lms.bank', 'label' => 'Bank Soal', 'roles' => ['dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2M9 21h6M12 3v18', 'pattern' => 'lms.bank'],
                            ['route' => 'nilai.input', 'label' => 'Nilai', 'roles' => ['dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M3 17l6-6 4 4 8-8', 'pattern' => 'nilai.*|khs.*|transkrip.*'],
                            ['route' => $skripsiRoute, 'label' => 'Skripsi', 'roles' => ['mahasiswa', 'dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M12 20h9', 'pattern' => 'skripsi.pengajuan|skripsi.pembimbing|bimbingan.*|sidang.*'],
                        ],
                        'Layanan Mahasiswa' => [
                            ['route' => 'keuangan.beasiswa', 'label' => 'Beasiswa', 'roles' => ['keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M12 2l2.5 5.5L20 8l-4 4 1 6-5-3-5 3 1-6-4-4', 'pattern' => 'keuangan.beasiswa'],
                            ['route' => 'skripsi.yudisium', 'label' => 'Yudisium', 'roles' => ['pimpinan', 'superadmin'], 'icon' => 'M12 3l7 4v5c0 4.5-3 7.5-7 9', 'pattern' => 'skripsi.yudisium'],
                            ['route' => 'publik.alumni', 'label' => 'Alumni', 'roles' => null, 'icon' => 'M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5', 'pattern' => 'publik.alumni'],
                        ],
                        'Keuangan' => [
                            ['route' => $keuanganRoute, 'label' => 'Keuangan', 'roles' => ['mahasiswa', 'keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2-2H5', 'pattern' => 'keuangan.verifikasi|keuangan.tagihan|keuangan.bayar*|keuangan.denda|kwitansi.*'],
                            ['route' => 'keuangan.verifikasi', 'label' => 'Verifikasi', 'roles' => ['keuangan', 'superadmin'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'pattern' => 'keuangan.verifikasi'],
                            ['route' => 'keuangan.denda', 'label' => 'Aturan Denda', 'roles' => ['keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z', 'pattern' => 'keuangan.denda'],
                        ],
                        'Operasional' => [
                            ['route' => $suratRoute, 'label' => 'Surat', 'roles' => ['mahasiswa', 'keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M22 12h-6l-2 3h-4l-2-3H2', 'pattern' => 'surat.*'],
                            ['route' => 'kalender.index', 'label' => 'Kalender', 'roles' => null, 'icon' => 'M8 7V3m8 4V3M3 11h18', 'pattern' => 'kalender.*'],
                            ['route' => 'helpdesk.index', 'label' => 'Helpdesk', 'roles' => null, 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'pattern' => 'helpdesk.*'],
                            ['route' => 'publik.pengumuman', 'label' => 'Pengumuman', 'roles' => null, 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'pattern' => 'notifikasi.*|publik.pengumuman*'],
                            ['route' => 'ai.chat', 'label' => 'Asisten AI', 'roles' => null, 'icon' => 'M12 3v3m0 12v3', 'pattern' => 'ai.*|analitik.at-risk'],
                        ],
                        'Analitik' => [
                            ['route' => 'laporan.index', 'label' => 'Rekap', 'roles' => ['pimpinan', 'superadmin', 'keuangan'], 'icon' => 'M3 3v18h18', 'pattern' => 'laporan.*|pddikti.*'],
                            ['route' => 'pmb.funnel', 'label' => 'Funnel PMB', 'roles' => ['pimpinan', 'superadmin'], 'icon' => 'M4 4h16v12H4z', 'pattern' => 'pmb.funnel'],
                        ],
                        'Sistem' => [
                            ['route' => 'admin.users', 'label' => 'Pengguna', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'pattern' => 'admin.users'],
                            ['route' => 'admin.dosen', 'label' => 'Dosen & Tendik', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'pattern' => 'admin.dosen'],
                            ['route' => 'admin.master', 'label' => 'Data Master', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M4 4h16v12H4z', 'pattern' => 'admin.master'],
                            ['route' => 'admin.impor', 'label' => 'Impor CSV', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4', 'pattern' => 'admin.impor*'],
                            ['route' => 'admin.integrasi', 'label' => 'Integrasi', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M4 21v-7m0-4V3m8 18v-9', 'pattern' => 'admin.integrasi'],
                            ['route' => 'notifikasi.broadcast', 'label' => 'Broadcast', 'roles' => ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'], 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'pattern' => 'notifikasi.broadcast'],
                            ['route' => 'admin.audit', 'label' => 'Audit Log', 'roles' => ['superadmin', 'admin_prodi', 'pimpinan'], 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z', 'pattern' => 'admin.audit'],
                            ['route' => 'media.generate', 'label' => 'Generator AI', 'roles' => ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'], 'icon' => 'M12 3v3m0 12v3', 'pattern' => 'media.*'],
                        ],
                    ];
                @endphp
                @auth
                @foreach($menuGroups as $group => $items)
                    @php $visible = collect($items)->filter(fn ($it) => $it['roles'] === null || auth()->user()->hasAnyRole($it['roles'])); @endphp
                    @if($visible->isNotEmpty())
                        <div class="kampius-nav-group">{{ $group }}</div>
                        @foreach($visible as $item)
                            @php try { $url = route($item['route']); } catch (Throwable) { continue; } @endphp
                            <a class="kampius-nav-link {{ request()->routeIs(...explode('|', $item['pattern'])) ? 'active' : '' }}" href="{{ $url }}" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="{{ $item['icon'] }}" /></svg>
                                <span class="kampius-nav-label">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    @endif
                @endforeach
                @endauth
            </nav>
            <div class="kampius-sidebar-footer">
                @auth
                <div class="kampius-user-chip">
                    <span class="avatar avatar-sm bg-primary text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="meta">
                        <span class="d-block text-truncate fw-bold small">{{ auth()->user()->name }}</span>
                        <span class="d-block text-secondary" style="font-size:.72rem">{{ auth()->user()->roles->pluck('name')->join(', ') }}</span>
                    </span>
                </div>
                @endauth
            </div>
        </aside>
        <div class="page-wrapper">
            @unless(request()->routeIs('home') && !auth()->check())
            <header class="kampius-topbar d-print-none">
                <div class="container-xl d-flex align-items-center gap-2 py-2">
                    <button type="button" class="btn btn-sm btn-outline-primary d-lg-none" data-kampius-nav aria-label="Buka navigasi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="d-none d-md-block">
                        @hasSection('breadcrumb')
                            @yield('breadcrumb')
                        @else
                            <ol class="breadcrumb" aria-label="Breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">@yield('page-title', 'Ringkasan')</li>
                            </ol>
                        @endif
                    </div>
                    @hasSection('public-nav')
                    @else
                    @auth
                    <form action="{{ route('pencarian') }}" method="GET" role="search" class="kampius-search ms-auto d-none d-sm-block">
                        <div class="input-group">
                            <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari prodi, MK, pengumuman…" aria-label="Pencarian global" value="{{ request('q') }}">
                            <button class="btn btn-sm btn-outline-primary" type="submit">Cari</button>
                        </div>
                    </form>
                    @endauth
                    <span class="kampius-period d-none d-xl-inline" title="Tahun akademik aktif">{{ $periodeAktif ?? '—' }}</span>
                    @auth
                    <div class="nav-item dropdown d-none d-sm-block">
                        <a href="#" class="nav-link position-relative px-2" data-bs-toggle="dropdown" aria-label="Notifikasi ({{ $notifikasiCount ?? 0 }})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                            @if(($notifikasiCount ?? 0) > 0)
                                <span class="kampius-notif-dot">{{ min(9, $notifikasiCount) }}{{ $notifikasiCount > 9 ? '+' : '' }}</span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" style="min-width: 300px">
                            <h6 class="dropdown-header">Pengumuman terbaru</h6>
                            @forelse($notifikasiTerbaru ?? [] as $n)
                                <a class="dropdown-item" href="{{ route('publik.pengumuman.detail', $n->id) }}">
                                    <span class="d-block text-truncate fw-bold">{{ $n->judul }}</span>
                                    <small class="text-secondary">{{ $n->published_at?->format('d M Y') }}</small>
                                </a>
                            @empty
                                <span class="dropdown-item-text text-secondary">Belum ada pengumuman.</span>
                            @endforelse
                        </div>
                    </div>
                    @endauth
                    @endif
                    <div class="nav-item dropdown">
                        @auth
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Akun">
                            <span class="avatar avatar-sm bg-primary text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a class="dropdown-item" href="{{ route('profil') }}">Profil saya</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Keluar</button>
                            </form>
                        </div>
                        @endauth
                    </div>
                </div>
            </header>
            @endunless
            @hasSection('no-header')
            @else
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            @hasSection('pretitle')
                                <div class="page-pretitle">@yield('pretitle')</div>
                            @endif
                            <h2 class="page-title">@yield('page-title', 'Dashboard')</h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            @yield('page-actions')
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <div class="page-body">
                <div class="container-xl" id="main-content" tabindex="-1">
                    @include('layouts._alerts')
                    @yield('content')
                </div>
            </div>
        </div>
        </div>
    @endif
    <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
                <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">KAMPUS • SIMAK Kuliah — Laravel {{ app()->version() }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</div>
<script>
(function () {
    var body = document.body;
    try {
        if (window.localStorage && localStorage.getItem('kampius:sidebar') === 'collapsed') {
            body.classList.add('kampius-collapsed');
        }
    } catch (e) {}
    function onClick(e) {
        var t = e.target.closest('[data-kampius-collapse]');
        if (t) {
            body.classList.toggle('kampius-collapsed');
            try {
                localStorage.setItem('kampius:sidebar', body.classList.contains('kampius-collapsed') ? 'collapsed' : 'open');
            } catch (err) {}
        }
        if (e.target.closest('[data-kampius-nav]')) {
            body.classList.toggle('kampius-nav-open');
        }
        if (e.target.closest('[data-kampius-nav-close]') || (!e.target.closest('.kampius-sidebar') && !e.target.closest('[data-kampius-nav]') && body.classList.contains('kampius-nav-open'))) {
            body.classList.remove('kampius-nav-open');
        }
    }
    function onKey(e) {
        if (e.key === 'Escape') { body.classList.remove('kampius-nav-open'); }
    }
    document.addEventListener('click', onClick);
    document.addEventListener('keydown', onKey);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
<script>
// M-060: daftarkan service worker portal mahasiswa.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
}
</script>
<livewire:scripts />
@stack('scripts')
</body>
</html>
