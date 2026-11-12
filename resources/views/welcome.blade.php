@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', 'Kampius — Sistem Informasi Akademik Kampus')

@section('content')
{{-- ============ NAVBAR LANDING ============ --}}
<header class="navbar navbar-expand-md d-print-none sticky-top bg-white border-bottom">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landing-menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ route('home') }}" class="text-decoration-none"><strong>KAMPIUS</strong> <span class="text-secondary fw-normal">SIMAK Kuliah</span></a>
        </h1>
        <div class="collapse navbar-collapse" id="landing-menu">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                <li class="nav-item"><a class="nav-link" href="#modul">Modul</a></li>
                <li class="nav-item"><a class="nav-link" href="#alur">Alur Kerja</a></li>
                <li class="nav-item"><a class="nav-link" href="#peran">Peran</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('dokumen.index') }}">Dokumentasi</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
            </ul>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Masuk</a>
                <a href="{{ route('login') }}" class="btn btn-primary">Coba Demo</a>
            </div>
        </div>
    </div>
</header>

{{-- ============ HERO (mockup AI ERP for Education) ============ --}}
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
                    <a href="{{ route('login') }}" class="btn btn-light btn-lg">Coba Demo</a>
                    <a href="#modul" class="btn btn-outline-light btn-lg">Lihat Modul</a>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header bg-dark text-white border-0">
                        <span class="me-1"><span class="badge badge-dot bg-white"></span></span>
                        <span class="me-1"><span class="badge badge-dot bg-yellow"></span></span>
                        <span class="me-3"><span class="badge badge-dot bg-blue"></span></span>
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
                            <div class="col-auto d-flex gap-2">
                                <span class="avatar avatar-xs bg-blue-lt">U</span>
                                <input class="form-control form-control-sm" style="width: 140px" placeholder="" disabled>
                            </div>
                        </div>
                        <div class="row row-cards mb-3">
                            <div class="col-6 col-md-3">
                                <div class="card card-sm"><div class="card-body">
                                    <div class="text-secondary small">Total Students</div>
                                    <div class="h2 mb-0">{{ number_format($stats['mahasiswa'] ?? 0) }}</div>
                                </div></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card card-sm"><div class="card-body">
                                    <div class="text-secondary small">New Admissions</div>
                                    <div class="h2 mb-0">{{ number_format($stats['pmb_baru'] ?? 0) }}</div>
                                </div></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card card-sm"><div class="card-body">
                                    <div class="text-secondary small">Pending Fees</div>
                                    <div class="progress progress-sm mt-2"><div class="progress-bar bg-success" style="width: {{ min(100, ($stats['hadir_pct'] ?? 0)) }}%"></div></div>
                                </div></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card card-sm"><div class="card-body text-center">
                                    <div class="h3 mb-0">Rp {{ number_format(($stats['piutang'] ?? 0), 0, ',', '.') }}</div>
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
                                            <svg viewBox="0 0 42 42" style="height: 110px" role="img" aria-label="Present {{ $pct }}%">
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#e6e8e9" stroke-width="5"></circle>
                                                <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="#2fb344" stroke-width="5" stroke-dasharray="{{ round($c * $pct / 100, 1) }} {{ round($c, 1) }}" stroke-linecap="round" transform="rotate(-90 21 21)"></circle>
                                                <text x="21" y="20" text-anchor="middle" font-size="5" fill="#6c7a91">Present</text>
                                                <text x="21" y="27" text-anchor="middle" font-size="7" font-weight="bold" fill="#1d273b">{{ $pct }}%</text>
                                            </svg>
                                        </div>
                                        <div class="col small text-secondary">
                                            <div><span class="badge badge-dot bg-yellow"></span> Absent</div>
                                            <div><span class="badge badge-dot bg-blue"></span> Late</div>
                                            <div><span class="badge badge-dot bg-secondary"></span> Other</div>
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
                                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100 mt-2" style="height: 80px" role="img" aria-label="Grade trend">
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
                                                <span class="badge bg-blue me-1">&#9632;</span>{{ $n->judul }}
                                            </div>
                                        @empty
                                            <div class="list-group-item small text-secondary">New Assignment Uploaded</div>
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
                                                        <td><span class="badge bg-{{ $m->status === 'aktif' ? 'success' : 'warning' }}">{{ $m->status === 'aktif' ? 'Active' : 'At Risk' }}</span></td>
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
                                            <div class="list-group-item small text-secondary">Fee Payment Reminder</div>
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

{{-- ============ STATS ============ --}}
<div class="container-xl mt-4" id="fitur">
    <div class="card card-lg">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-6 col-md-3"><div class="display-6">{{ number_format($stats['mahasiswa'] ?? 0) }}</div><div class="text-secondary">Profil mahasiswa</div></div>
                <div class="col-6 col-md-3"><div class="display-6">{{ number_format($stats['prodi'] ?? 0) }}</div><div class="text-secondary">Program studi</div></div>
                <div class="col-6 col-md-3"><div class="display-6">{{ number_format($stats['matakuliah'] ?? 0) }}</div><div class="text-secondary">Mata kuliah</div></div>
                <div class="col-6 col-md-3"><div class="display-6">{{ number_format($stats['audit'] ?? 0) }}</div><div class="text-secondary">Audit tercatat</div></div>
            </div>
        </div>
    </div>
</div>

{{-- ============ MODUL ============ --}}
<div class="container-xl mt-4" id="modul">
    <div class="text-center mb-3">
        <div class="text-info text-uppercase small mb-1">Modul Lengkap</div>
        <h2>Satu platform untuk seluruh operasional kampus</h2>
        <p class="text-secondary">Setiap modul saling terhubung dalam satu database — tanpa entri ganda.</p>
    </div>
    @php
        $moduls = [
            ['PMB', 'Pendaftaran, seleksi, verifikasi berkas, pengumuman, daftar ulang + NIM otomatis.', 'pmb.create'],
            ['Mahasiswa', 'Profil, status, riwayat, PA, dokumen kemahasiswaan.', 'publik.prodi'],
            ['Akademik', 'Tahun akademik, kurikulum, kelas, jadwal, ruang.', 'admin.master'],
            ['KRS & KHS', 'KRS online + approval PA, KHS & IPK tertimbang SKS.', 'mahasiswa.krs.pengajuan'],
            ['Kurikulum', 'Matakuliah, SKS, prasyarat, semester, anti-bentrok.', 'admin.master'],
            ['Dosen', 'Data dosen, jurnal mengajar, bimbingan, penilaian sidang.', 'dosen.krs.persetujuan'],
            ['Keuangan', 'Tagihan massal, VA Tripay, verifikasi, kwitansi, beasiswa.', 'keuangan.bayar'],
            ['Transkrip', 'Transkrip sementara & akhir, IPK, syarat kelulusan.', 'publik.pengumuman'],
            ['Wisuda', 'Kelayakan sidang, yudisium, surat keterangan lulus.', 'skripsi.pengajuan'],
            ['Laporan', 'Dashboard pimpinan, ekspor PDDikti, rekap pembayaran.', 'dashboard.pimpinan'],
        ];
    @endphp
    <div class="row row-cards">
        @foreach($moduls as [$nama, $deskripsi, $rute])
            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body">
                    <h3 class="card-title">{{ $nama }}</h3>
                    <p class="text-secondary">{{ $deskripsi }}</p>
                    <a href="{{ route($rute) }}">Buka modul →</a>
                </div></div>
            </div>
        @endforeach
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 bg-primary text-white"><div class="card-body d-flex flex-column justify-content-center">
                <h3 class="card-title text-white">+ Surat, Notifikasi &amp; AI</h3>
                <p class="text-white-50">Surat resmi + QR verifikasi, broadcast push, generate gambar ComfyUI.</p>
                <a href="{{ route('login') }}" class="text-white"><u>Coba demo →</u></a>
            </div></div>
        </div>
    </div>
</div>

{{-- ============ ALUR ============ --}}
<div class="bg-light mt-4" id="alur">
    <div class="container-xl py-5">
        <div class="text-center mb-3">
            <div class="text-info text-uppercase small mb-1">Lifecycle dalam satu peta</div>
            <h2>Data bergerak mengikuti proses kampus</h2>
        </div>
        <div class="row row-cards">
            @php
                $alurs = [
                    ['01', 'Daftar & Seleksi', 'PMB online → verifikasi berkas → lulus → daftar ulang (akun + NIM + tagihan otomatis).'],
                    ['02', 'Kuliah & Nilai', 'KRS → presensi QR → materi/tugas/kuis → nilai → KHS/IPK → transkrip.'],
                    ['03', 'Bayar & Lulus', 'Tagihan VA/bukti manual → verifikasi → kwitansi → skripsi → sidang → lulus.'],
                ];
            @endphp
            @foreach($alurs as [$no, $judul, $deskripsi])
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body text-center">
                        <span class="badge bg-blue-lt mb-2">{{ $no }}</span>
                        <h3 class="card-title">{{ $judul }}</h3>
                        <p class="text-secondary">{{ $deskripsi }}</p>
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ============ PERAN ============ --}}
<div class="container-xl mt-4" id="peran">
    <div class="text-center mb-3">
        <div class="text-info text-uppercase small mb-1">Dibuat untuk setiap peran</div>
        <h2>Satu sumber data, tampilan yang relevan</h2>
    </div>
    <div class="row row-cards">
        @php
            $perans = [
                ['R', 'Rektor', 'Pertumbuhan, retensi & graduation.', true],
                ['B', 'BAAK', 'Antrian administrasi & status mahasiswa.', false],
                ['F', 'Finance', 'Piutang & payment ledger.', false],
                ['D', 'Dosen', 'KRS bimbingan & nilai kelas.', false],
                ['M', 'Mahasiswa', 'KHS & layanan mandiri.', false],
            ];
        @endphp
        @foreach($perans as [$inisial, $nama, $deskripsi, $gelap])
            <div class="col-6 col-lg">
                <div class="card h-100 {{ $gelap ? 'bg-dark text-white' : '' }}"><div class="card-body text-center">
                    <span class="avatar {{ $gelap ? 'bg-dark-lt' : 'bg-blue-lt' }} mb-2">{{ $inisial }}</span>
                    <h3 class="card-title {{ $gelap ? 'text-white' : '' }}">{{ $nama }}</h3>
                    <p class="{{ $gelap ? 'text-white-50' : 'text-secondary' }} small">{{ $deskripsi }}</p>
                </div></div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ KEAMANAN ============ --}}
<div class="container-xl mt-4">
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="text-info text-uppercase small mb-1">Keamanan enterprise</div>
                    <h2>Data akademik terlindungi berlapis</h2>
                    <p class="text-secondary">Role-based access di setiap rute, otorisasi kepemilikan data, audit trail semua aksi sensitif, transaksi keuangan terkunci, signature webhook terverifikasi.</p>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-green-lt">RBAC 6 peran</span>
                        <span class="badge bg-green-lt">IDOR protected</span>
                        <span class="badge bg-green-lt">Audit trail</span>
                        <span class="badge bg-green-lt">HMAC callback</span>
                        <span class="badge bg-green-lt">Rate limiting</span>
                        <span class="badge bg-green-lt">Token expiry</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ FAQ ============ --}}
<div class="container-xl mt-4" id="faq">
    <div class="text-center mb-3">
        <div class="text-info text-uppercase small mb-1">FAQ</div>
        <h2>Pertanyaan umum</h2>
    </div>
    <div class="accordion" id="faqAccordion">
        @php
            $faqs = [
                ['Siapa yang bisa memakai aplikasi ini?', 'Admin/BAAK, keuangan, dosen, mahasiswa, dan pimpinan — masing-masing dengan hak akses berbeda yang ditegakkan server.'],
                ['Bagaimana mahasiswa membayar UKT?', 'Lewat VA Tripay (otomatis terverifikasi via callback) atau upload bukti transfer manual yang diverifikasi petugas keuangan.'],
                ['Apakah data nilai aman dari perubahan?', 'Nilai yang sudah dikunci tidak bisa diubah. Setiap perubahan tercatat di audit trail beserta pelakunya.'],
                ['Apakah ada aplikasi mobile?', 'Ya — API v1 (Sanctum) siap dipakai aplikasi Flutter: jadwal, absensi QR, tugas, nilai, tagihan, dan notifikasi.'],
                ['Bagaimana cara mencoba demo?', 'Klik Coba Demo dan masuk dengan akun demo yang disediakan admin kampus.'],
            ];
        @endphp
        @foreach($faqs as $i => [$tanya, $jawab])
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">{{ $tanya }}</button>
                </h2>
                <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">{{ $jawab }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ CTA AKHIR ============ --}}
<div class="container-xl mt-4">
    <div class="card card-lg bg-primary text-white">
        <div class="card-body py-5 text-center">
            <h2 class="text-white display-6">Mulai dari fondasi akademik yang bisa dipercaya.</h2>
            <p class="text-white-50">Demo data sudah tersedia. Masuk sebagai admin, BAAK, finance, dosen wali, atau mahasiswa.</p>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="{{ route('login') }}" class="btn btn-light btn-lg">Buka Akun Demo</a>
                <a href="{{ route('pmb.create') }}" class="btn btn-outline-light btn-lg">Daftar PMB</a>
            </div>
        </div>
    </div>
</div>

<footer class="container-xl d-flex flex-wrap justify-content-between text-secondary small mt-4 mb-3">
    <span>© {{ date('Y') }} Kampius — SIMAK Kuliah</span>
    <span>
        <a href="{{ route('publik.prodi') }}" class="me-3">Program Studi</a>
        <a href="{{ route('publik.pengumuman') }}" class="me-3">Pengumuman</a>
        <a href="{{ route('dokumen.index') }}" class="me-3">Dokumentasi</a>
        <a href="{{ route('pmb.create') }}">PMB</a>
    </span>
</footer>
@endsection
