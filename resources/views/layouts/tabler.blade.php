<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'SIMAK Kuliah'))</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <livewire:styles />
    @stack('styles')
</head>
<body>
<div class="page">
    @hasSection('public-nav')
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#public-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="{{ route('home') }}" class="text-decoration-none">SIMAK Kuliah</a>
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
                    <a href="{{ route('dashboard') }}" class="text-decoration-none text-white">SIMAK Kuliah</a>
                </h1>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        @php
                            $menu = [
                                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l9-9 9 9M9 21V12h6v9', 'pattern' => 'dashboard'],
                                ['route' => 'admin.pmb.verifikasi', 'label' => 'PMB', 'icon' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2', 'pattern' => 'admin.pmb*|pmb.*'],
                                ['route' => 'mahasiswa.krs.pengajuan', 'label' => 'KRS', 'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4', 'pattern' => 'mahasiswa.krs*|dosen.krs*|krs.*'],
                                ['route' => 'lms.materi', 'label' => 'LMS', 'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 1 4 4.5v15zM4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5', 'pattern' => 'lms.*|absensi.*'],
                                ['route' => 'nilai.input', 'label' => 'Nilai', 'icon' => 'M3 17l6-6 4 4 8-8M14 7h7v7', 'pattern' => 'nilai.*|khs.*|transkrip.*'],
                                ['route' => 'skripsi.pengajuan', 'label' => 'Skripsi', 'icon' => 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z', 'pattern' => 'skripsi.*|bimbingan.*|sidang.*'],
                                ['route' => 'keuangan.tagihan', 'label' => 'Keuangan', 'icon' => 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm0 3h18M7 15h.01', 'pattern' => 'keuangan.*|kwitansi.*'],
                                ['route' => 'surat.pengajuan', 'label' => 'Surat', 'icon' => 'M22 12h-6l-2 3h-4l-2-3H2M5.5 5.1L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.7 4H7.3a2 2 0 0 0-1.8 1.1z', 'pattern' => 'surat.*'],
                                ['route' => 'media.generate', 'label' => 'Generate Gambar', 'icon' => 'M12 3v3m0 12v3M5.6 5.6l2.1 2.1m8.6 8.6l2.1 2.1M3 12h3m12 0h3M5.6 18.4l2.1-2.1m8.6-8.6l2.1-2.1M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z', 'pattern' => 'media.*'],
                                ['route' => 'publik.pengumuman', 'label' => 'Pengumuman', 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M10.3 21a1.9 1.9 0 0 0 3.4 0', 'pattern' => 'notifikasi.*|publik.pengumuman*'],
                                ['route' => 'dashboard.pimpinan', 'label' => 'Laporan', 'icon' => 'M3 3v18h18M7 15l4-4 3 3 5-6', 'pattern' => 'dashboard.pimpinan|pddikti.*'],
                                ['route' => 'admin.integrasi', 'label' => 'Integrasi', 'icon' => 'M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h6m2-6h6m2 8h6', 'pattern' => 'admin.integrasi'],
                            ];
                        @endphp
                        @foreach($menu as $item)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="{{ $item['icon'] }}" /></svg>
                                    </span>
                                    <span class="nav-link-title">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
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
            <div class="container-xl">
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
                            <li class="list-inline-item">SIMAK Kuliah — Laravel {{ app()->version() }}</li>
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
