@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', config('app.name', 'SIMAK Kuliah') . ' — Smart Campus')

@section('content')
{{-- HERO full-bleed ala mockup AI ERP for Education --}}
<div class="bg-primary text-white">
    <div class="container-xl py-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <h1 class="display-2 fw-bold text-white">SIMAK ERP<br>for Education</h1>
                <p class="lead bg-white text-primary d-inline-block px-3 py-1">Smart Campus. Smarter Decisions.</p>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <span class="avatar bg-white text-primary">&#127891;</span>
                    <strong>Automate &middot; Analyze &middot; Improve</strong>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('login') }}" class="btn btn-light btn-lg">Coba demo</a>
                    <a href="{{ route('publik.prodi') }}" class="btn btn-outline-light btn-lg">Lihat prodi</a>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-dark text-white border-0">
                        <span class="badge bg-dark-lt text-white-50">local/app/admin</span>
                        <div class="card-actions">
                            <span class="me-1"><span class="badge badge-dot bg-white"></span></span>
                            <span class="me-1"><span class="badge badge-dot bg-red"></span></span>
                            <span class="me-1"><span class="badge badge-dot bg-yellow"></span></span>
                            <span class="badge badge-dot bg-green"></span>
                        </div>
                    </div>
                    <div class="card-body bg-light text-dark">
                        <div class="row align-items-center mb-3">
                            <div class="col"><h3 class="card-title mb-0">Welcome Admin!</h3></div>
                            <div class="col-auto"><input class="form-control form-control-sm" placeholder="Search..." disabled></div>
                        </div>
                        <div class="row row-cards mb-3">
                            <div class="col-3">
                                <div class="card card-sm"><div class="card-body text-center">
                                    <div class="text-secondary small">Mahasiswa Aktif</div>
                                    <div class="h2 mb-0">{{ number_format($stats['mahasiswa'] ?? 0) }}</div>
                                </div></div>
                            </div>
                            <div class="col-3">
                                <div class="card card-sm"><div class="card-body text-center">
                                    <div class="text-secondary small">PMB Baru</div>
                                    <div class="h2 mb-0">{{ number_format($stats['pmb_baru'] ?? 0) }}</div>
                                </div></div>
                            </div>
                            <div class="col-3">
                                <div class="card card-sm"><div class="card-body text-center">
                                    <div class="text-secondary small">KRS Pending</div>
                                    <div class="h2 mb-0">{{ $stats['krs_pending'] ?? 0 }}</div>
                                    <div class="progress progress-sm mt-1"><div class="progress-bar bg-success" style="width: {{ min(100, ($stats['hadir_pct'] ?? 0)) }}%"></div></div>
                                </div></div>
                            </div>
                            <div class="col-3">
                                <div class="card card-sm"><div class="card-body text-center">
                                    <div class="h2 mb-0">Rp {{ number_format(($stats['piutang'] ?? 0) / 1000000, 1) }} jt</div>
                                    <div class="progress progress-sm mt-1"><div class="progress-bar" style="width: 65%"></div></div>
                                </div></div>
                            </div>
                        </div>
                        <div class="row row-cards mb-3">
                            <div class="col-md-7">
                                <div class="card h-100"><div class="card-body">
                                    <h4 class="card-title">Attendance Overview</h4>
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            @php
                                                $pct = (int) ($stats['hadir_pct'] ?? 0);
                                                $bd = $stats['absensi_breakdown'] ?? [];
                                                $r = 15.9; $c = 2 * pi() * $r;
                                            @endphp
                                            <svg viewBox="0 0 42 42" style="height: 110px" role="img" aria-label="Kehadiran">
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#e6e8e9" stroke-width="5"></circle>
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#2fb344" stroke-width="5" stroke-dasharray="{{ round($c * $pct / 100, 1) }} {{ round($c, 1) }}" stroke-linecap="round" transform="rotate(-90 21 21)"></circle>
                                                <text x="21" y="20" text-anchor="middle" font-size="5" fill="#6c7a91">Present</text>
                                                <text x="21" y="27" text-anchor="middle" font-size="7" font-weight="bold" fill="#1d273b">{{ $pct }}%</text>
                                            </svg>
                                        </div>
                                        <div class="col small">
                                            <div><span class="badge badge-dot bg-yellow"></span> Absent &mdash; {{ $bd['alpa'] ?? 0 }}</div>
                                            <div><span class="badge badge-dot bg-blue"></span> Izin &mdash; {{ $bd['izin'] ?? 0 }}</div>
                                            <div><span class="badge badge-dot bg-secondary"></span> Telat &mdash; {{ $bd['telat'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    @php
                                        $grades = $stats['grades'] ?? [];
                                        $order = ['A', 'B', 'C', 'D', 'E'];
                                        $vals = array_map(fn ($h) => (int) ($grades[$h] ?? 0), $order);
                                        $maks = max(1, max($vals));
                                        $w = 100; $h = 26;
                                        $titik = [];
                                        foreach ($vals as $i => $v) {
                                            $x = 6 + $i * (($w - 12) / max(1, count($vals) - 1));
                                            $y = $h - 3 - ($v / $maks) * ($h - 9);
                                            $titik[] = round($x, 1).'.'.round($y, 1);
                                        }
                                    @endphp
                                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100 mt-2" style="height: 80px" role="img" aria-label="Sebaran nilai">
                                        <polyline points="{{ implode(' ', $titik) }}" fill="none" stroke="#206bc4" stroke-width="1.5"></polyline>
                                        @foreach($titik as $t)
                                            @php [$cx, $cy] = explode('.', $t); @endphp
                                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="1.4" fill="#206bc4"></circle>
                                        @endforeach
                                    </svg>
                                    <div class="d-flex justify-content-between text-secondary small">
                                        @foreach($order as $h)
                                            <span>{{ $h }}</span>
                                        @endforeach
                                    </div>
                                </div></div>
                            </div>
                            <div class="col-md-5">
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white border-0 py-2"><h3 class="card-title text-white mb-0">Notifications</h3></div>
                                    <div class="list-group list-group-flush">
                                        @forelse($stats['notifikasi'] ?? [] as $n)
                                            <div class="list-group-item small text-truncate">
                                                <span class="badge bg-blue me-1">&#9679;</span>{{ $n->judul }}
                                            </div>
                                        @empty
                                            <div class="list-group-item small text-secondary">Belum ada pengumuman.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row row-cards">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header"><h3 class="card-title">Student List</h3></div>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter card-table">
                                            <thead><tr><th>Name</th><th>Class</th><th>Status</th></tr></thead>
                                            <tbody>
                                                @forelse($stats['top_mahasiswa'] ?? [] as $m)
                                                    <tr>
                                                        <td>{{ $m->user->name ?? $m->nim }}</td>
                                                        <td class="text-secondary">{{ $m->prodi->kode ?? '-' }}</td>
                                                        <td><span class="badge bg-{{ $m->status === 'aktif' ? 'success' : 'warning' }}">{{ $m->status === 'aktif' ? 'Active' : $m->status }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-secondary">Belum ada data.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header"><h3 class="card-title">Notifications</h3></div>
                                    <div class="list-group list-group-flush">
                                        @forelse($stats['notifikasi'] ?? [] as $n)
                                            <div class="list-group-item small">
                                                <span class="badge bg-green-lt me-1">+</span>{{ $n->judul }}
                                                <div class="text-secondary">{{ $n->published_at?->format('d M Y') }}</div>
                                            </div>
                                        @empty
                                            <div class="list-group-item small text-secondary">Belum ada pengumuman.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <svg viewBox="0 0 1440 60" preserveAspectRatio="none" style="height: 40px; width: 100%; display: block" aria-hidden="true"><path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#ffffff"></path></svg>
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
        <p class="text-secondary">Satu sumber data dari PMB hingga transkrip — setiap peran melihat tampilan yang relevan.</p>
    </div>
    <div class="col-lg-9">
        <div class="row row-cards">
            @php
                $alur = [
                    ['01', 'bg-blue-lt', 'PMB → Mahasiswa', 'Pendaftaran, seleksi, registrasi ulang, generate NIM.', 'pmb.create'],
                    ['02', 'bg-green-lt', 'Akademik → Transkrip', 'Kurikulum, KRS, presensi, nilai, KHS, IPK.', 'mahasiswa.krs.pengajuan'],
                    ['03', 'bg-yellow-lt', 'Keuangan → Laporan', 'Invoice, allocation, ledger, dashboard eksekutif.', 'keuangan.bayar'],
                ];
            @endphp
            @foreach($alur as [$no, $warna, $judul, $deskripsi, $rute])
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body">
                        <span class="badge {{ $warna }} mb-2">{{ $no }}</span>
                        <h3 class="card-title">{{ $judul }}</h3>
                        <p class="text-secondary">{{ $deskripsi }}</p>
                        <a href="{{ route($rute) }}">Pelajari alurnya →</a>
                    </div></div>
                </div>
            @endforeach
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
            </div>
            <div class="col-lg-9">
                <div class="row row-cards">
                    @php
                        $peran = [
                            ['R', 'bg-dark text-white', 'Rektor', 'Retention & graduation', true],
                            ['B', '', 'BAAK', 'KRS & status mahasiswa', false],
                            ['F', '', 'Finance', 'Piutang & payment ledger', false],
                            ['M', '', 'Mahasiswa', 'KHS & layanan mandiri', false],
                        ];
                    @endphp
                    @foreach($peran as [$inisial, $gelap, $nama, $deskripsi, $isGelap])
                        <div class="col-md-3">
                            <div class="card h-100 {{ $gelap }}"><div class="card-body">
                                <span class="avatar {{ $isGelap ? 'bg-dark-lt' : 'bg-blue-lt' }} mb-2">{{ $inisial }}</span>
                                <h3 class="card-title {{ $isGelap ? 'text-white' : '' }}">{{ $nama }}</h3>
                                <p class="{{ $isGelap ? 'text-white-50' : 'text-secondary' }}">{{ $deskripsi }}</p>
                            </div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CTA --}}
<div class="card card-lg bg-primary text-white mt-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="text-white">Mulai dari fondasi akademik yang bisa dipercaya.</h2>
                <p class="text-white-50 mb-0">Demo data sudah tersedia. Masuk sebagai admin, BAAK, finance, dosen wali, atau mahasiswa.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('login') }}" class="btn btn-light">Buka akun demo</a>
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
