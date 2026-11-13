<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KAMPIUS — University ERP & Academic Management System: PMB, akademik, keuangan, dan pelaporan kampus terpadu.">
    <meta property="og:title" content="@yield('title', config('app.name', 'KAMPIUS'))">
    <meta property="og:description" content="Kelola PMB, KRS, nilai, keuangan, dan laporan dalam satu sistem kampus terpadu.">
    <meta property="og:type" content="website">
    <title>@yield('title', config('app.name', 'KAMPIUS'))</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <livewire:styles />
    @stack('styles')
</head>
<body>
<a href="#main-content" class="visually-hidden-focusable">Lewati ke konten utama</a>
<div class="page">
    @hasSection('public-nav')
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#public-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="{{ route('home') }}" class="text-decoration-none">KAMPIUS <span class="text-secondary small fw-normal">ERP</span></a>
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
    @else
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-white">KAMPIUS <span class="text-white-50 small fw-normal">ERP</span></a>
                </h1>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        @php
                            $isDosenOnly = auth()->user()->hasAnyRole(['dosen']) && ! auth()->user()->hasAnyRole(['mahasiswa']);
                            $krsRoute = $isDosenOnly ? 'dosen.krs.persetujuan' : 'mahasiswa.krs.pengajuan';
                            $keuanganRoute = auth()->user()->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi']) ? 'keuangan.tagihan' : 'keuangan.bayar';
                            $suratRoute = auth()->user()->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi']) ? 'surat.nomor' : 'surat.pengajuan';
                            $skripsiRoute = $isDosenOnly ? 'skripsi.pembimbing' : 'skripsi.pengajuan';
                            $menuGroups = [
                                'DASHBOARD' => [
                                    ['route' => 'dashboard', 'label' => 'Ringkasan', 'roles' => null, 'icon' => 'M3 12l9-9 9 9M9 21V12h6v9', 'pattern' => 'dashboard'],
                                    ['route' => 'dashboard.pimpinan', 'label' => 'Dashboard Pimpinan', 'roles' => ['pimpinan', 'superadmin'], 'icon' => 'M3 3v18h18M7 15l-4-4 3 3 5-6', 'pattern' => 'dashboard.pimpinan'],
                                ],
                                'AKADEMIK' => [
                                    ['route' => 'admin.pmb.verifikasi', 'label' => 'PMB', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M8 7V3m8 4V3M3 11h18', 'pattern' => 'admin.pmb*'],
                                    ['route' => $krsRoute, 'label' => 'KRS', 'roles' => ['mahasiswa', 'dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0 2-2h-2', 'pattern' => 'mahasiswa.krs*|dosen.krs*|krs.*'],
                                    ['route' => 'dosen.wali', 'label' => 'Perwalian', 'roles' => ['dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'pattern' => 'dosen.wali'],
                                    ['route' => 'lms.materi', 'label' => 'LMS', 'roles' => ['dosen', 'mahasiswa', 'superadmin', 'admin_prodi'], 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 1 4 4.5v15z', 'pattern' => 'lms.*|absensi.*'],
                                    ['route' => 'nilai.input', 'label' => 'Nilai', 'roles' => ['dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M3 17l6-6 4 4 8-8', 'pattern' => 'nilai.*|khs.*|transkrip.*'],
                                    ['route' => $skripsiRoute, 'label' => 'Skripsi', 'roles' => ['mahasiswa', 'dosen', 'superadmin', 'admin_prodi'], 'icon' => 'M12 20h9', 'pattern' => 'skripsi.pengajuan|skripsi.pembimbing|bimbingan.*|sidang.*'],
                                    ['route' => 'skripsi.yudisium', 'label' => 'Yudisium', 'roles' => ['pimpinan', 'superadmin'], 'icon' => 'M12 3l7 4v5c0 4.5-3 7.5-7 9', 'pattern' => 'skripsi.yudisium'],
                                    ['route' => 'admin.kurikulum', 'label' => 'Kurikulum', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M4 4h16v12H4z', 'pattern' => 'admin.kurikulum|admin.master'],
                                ],
                                'KEUANGAN' => [
                                    ['route' => $keuanganRoute, 'label' => 'Keuangan', 'roles' => ['mahasiswa', 'keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2-2H5', 'pattern' => 'keuangan.*|kwitansi.*'],
                                    ['route' => 'keuangan.beasiswa', 'label' => 'Beasiswa', 'roles' => ['keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M12 2l2.5 5.5L20 8l-4 4 1 6-5-3-5 3 1-6-4-4', 'pattern' => 'keuangan.beasiswa'],
                                    ['route' => 'keuangan.denda', 'label' => 'Aturan Denda', 'roles' => ['keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z', 'pattern' => 'keuangan.denda'],
                                ],
                                'OPERASIONAL' => [
                                    ['route' => $suratRoute, 'label' => 'Surat', 'roles' => ['mahasiswa', 'keuangan', 'superadmin', 'admin_prodi'], 'icon' => 'M22 12h-6l-2 3h-4l-2-3H2', 'pattern' => 'surat.*'],
                                    ['route' => 'kalender.index', 'label' => 'Kalender', 'roles' => null, 'icon' => 'M8 7V3m8 4V3M3 11h18', 'pattern' => 'kalender.*'],
                                    ['route' => 'helpdesk.index', 'label' => 'Helpdesk', 'roles' => null, 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'pattern' => 'helpdesk.*'],
                                    ['route' => 'publik.pengumuman', 'label' => 'Pengumuman', 'roles' => null, 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9', 'pattern' => 'notifikasi.*|publik.pengumuman*'],
                                    ['route' => 'ai.chat', 'label' => 'AI Chatbot', 'roles' => null, 'icon' => 'M12 3v3m0 12v3', 'pattern' => 'ai.*|analitik.at-risk'],
                                ],
                                'ANALITIK' => [
                                    ['route' => 'laporan.index', 'label' => 'Rekap', 'roles' => ['pimpinan', 'superadmin', 'keuangan'], 'icon' => 'M3 3v18h18', 'pattern' => 'laporan.*|pddikti.*'],
                                    ['route' => 'pmb.funnel', 'label' => 'Funnel PMB', 'roles' => ['pimpinan', 'superadmin'], 'icon' => 'M4 4h16v12H4z', 'pattern' => 'pmb.funnel'],
                                ],
                                'SISTEM' => [
                                    ['route' => 'admin.users', 'label' => 'User', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'pattern' => 'admin.users'],
                                    ['route' => 'admin.dosen', 'label' => 'Dosen & Tendik', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'pattern' => 'admin.dosen'],
                                    ['route' => 'admin.master', 'label' => 'Data Master', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M4 4h16v12H4z', 'pattern' => 'admin.master'],
                                    ['route' => 'admin.jadwal', 'label' => 'Jadwal', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M8 7V3m8 4V3M3 11h18', 'pattern' => 'admin.jadwal'],
                                    ['route' => 'admin.impor', 'label' => 'Impor CSV', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M12 3v12m0 0l-4-4m4 4l4-4', 'pattern' => 'admin.impor*'],
                                    ['route' => 'admin.integrasi', 'label' => 'Integrasi', 'roles' => ['superadmin', 'admin_prodi'], 'icon' => 'M4 21v-7m0-4V3m8 18v-9', 'pattern' => 'admin.integrasi'],
                                    ['route' => 'admin.audit', 'label' => 'Audit Log', 'roles' => ['superadmin', 'admin_prodi', 'pimpinan'], 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z', 'pattern' => 'admin.audit'],
                                    ['route' => 'media.generate', 'label' => 'Generate Gambar', 'roles' => ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'], 'icon' => 'M12 3v3m0 12v3', 'pattern' => 'media.*'],
                                ],
                            ];
                        @endphp
                        @auth
                        @foreach($menuGroups as $group => $items)
                            @php $visible = collect($items)->filter(fn ($it) => $it['roles'] === null || auth()->user()->hasAnyRole($it['roles'])); @endphp
                            @if($visible->isNotEmpty())
                                <li class="nav-item mt-2"><span class="nav-link text-white-50 small text-uppercase fw-bold" style="font-size:.65rem;letter-spacing:.08em">{{ $group }}</span></li>
                                @foreach($visible as $item)
                                    @php try { $url = route($item['route']); } catch (Throwable) { continue; } @endphp
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}" href="{{ $url }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="{{ $item['icon'] }}" /></svg>
                                            </span>
                                            <span class="nav-link-title">{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        @endforeach
                        @endauth
                    </ul>
                </div>
            </div>
        </aside>
    @endif
    <div class="page-wrapper">
        @unless(request()->routeIs('home') && !auth()->check())
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                @hasSection('public-nav')
                @else
                <div class="navbar-nav flex-row order-md-last ms-auto">
                    @auth
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Akun">
                            <span class="avatar avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div class="d-none d-xl-block ps-2">
                                <div>{{ auth()->user()->name }}</div>
                                <div class="mt-1 small text-secondary">{{ auth()->user()->roles->pluck('name')->join(', ') }}</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Keluar</button>
                            </form>
                        </div>
                    </div>
                    @endauth
                </div>
                @endif
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
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-title">Periksa kembali isian Anda</h4>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item">KAMPIUS • SIMAK Kuliah — Laravel {{ app()->version() }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
<livewire:scripts />
@stack('scripts')
</body>
</html>
