@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', 'KAMPUS — University ERP & Academic Management System')

@push('styles')
<style>
    .kampius-landing-hero {
        background: linear-gradient(135deg, #10234B 0%, #1046A0 55%, #1769F5 100%);
        position: relative;
        overflow: hidden;
    }
    .kampius-landing-hero::after {
        content: "";
        position: absolute;
        right: -120px; top: -120px;
        width: 420px; height: 420px;
        border-radius: 50%;
        background: rgba(17,197,232,.22);
    }
    .kampius-landing-hero::before {
        content: "";
        position: absolute;
        left: -100px; bottom: -140px;
        width: 300px; height: 300px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
    }
    .kampius-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.2);
        color: #fff;
        border-radius: 999px;
        padding: .3rem .9rem;
        font-size: .78rem;
    }
    .kampius-eyebrow .dot { width: 8px; height: 8px; border-radius: 50%; background: #16A36A; }
    .kampius-mock {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 30px 80px rgba(0,0,0,.35);
        overflow: hidden;
        position: relative;
        z-index: 1;
    }
    .kampius-mock-topbar {
        display: flex;
        align-items: center;
        gap: .4rem;
        padding: .6rem .9rem;
        border-bottom: 1px solid #E8EEF7;
    }
    .kampius-mock-topbar i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .kampius-mock-body { display: flex; min-height: 320px; }
    .kampius-mock-side {
        width: 150px;
        background: #10234B;
        color: #c9d6ea;
        font-size: .68rem;
        padding: .8rem .6rem;
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .kampius-mock-side span { padding: .3rem .5rem; border-radius: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .kampius-mock-side span.on { background: rgba(23,105,245,.5); color: #fff; }
    .kampius-mock-main { flex: 1; padding: .9rem; background: #F5F8FD; min-width: 0; }
    .kampius-mock-stats { display: flex; gap: .5rem; margin-bottom: .6rem; }
    .kampius-mock-stat {
        flex: 1;
        background: #fff;
        border: 1px solid #E8EEF7;
        border-radius: 10px;
        padding: .45rem .55rem;
        min-width: 0;
    }
    .kampius-mock-stat b { display: block; font-size: .95rem; color: #10234B; }
    .kampius-mock-stat small { color: #77849A; font-size: .62rem; }
    .kampius-mock-chart {
        background: #fff;
        border: 1px solid #E8EEF7;
        border-radius: 10px;
        padding: .55rem .6rem;
        margin-bottom: .6rem;
    }
    .kampius-mock-bars { display: flex; align-items: flex-end; gap: 5px; height: 74px; }
    .kampius-mock-bars i { flex: 1; border-radius: 3px 3px 0 0; min-height: 6px; }
    .kampius-mock-rows { background: #fff; border: 1px solid #E8EEF7; border-radius: 10px; padding: .2rem .6rem; }
    .kampius-mock-rows div { display: flex; justify-content: space-between; font-size: .66rem; padding: .32rem 0; border-bottom: 1px solid #F1F4F9; color: #172B4D; }
    .kampius-mock-rows div:last-child { border-bottom: 0; }
    .kampius-modul { transition: box-shadow .2s ease, transform .2s ease; height: 100%; }
    .kampius-modul:hover { box-shadow: 0 14px 30px rgba(23,105,245,.14); transform: translateY(-3px); }
    .kampius-modul-icon {
        width: 46px; height: 46px; border-radius: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    .kampius-step-num {
        width: 44px; height: 44px; border-radius: 50%;
        background: #10234B; color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; margin-bottom: .5rem;
    }
    .kampius-step-line { position: absolute; top: 22px; left: 60px; right: -10px; height: 2px; background: #E0E8F3; }
    .kampius-role { transition: box-shadow .2s ease, transform .2s ease; height: 100%; }
    .kampius-role:hover { box-shadow: 0 14px 30px rgba(23,105,245,.14); transform: translateY(-3px); }
    @media (max-width: 991.98px) {
        .kampius-mock-side { display: none; }
    }
</style>
@endpush

@section('content')
{{-- ============ 2. HERO ============ --}}
<div class="kampius-landing-hero text-white">
    <div class="container-xl py-5" style="max-width: 1280px">
        <div class="row align-items-center g-4 position-relative" style="z-index:1">
            <div class="col-lg-5">
                <span class="kampius-eyebrow"><span class="dot"></span> Sistem Informasi Kampus Terpadu</span>
                <h1 class="display-5 fw-bold mt-3">Satu Platform untuk Seluruh Operasi Kampus</h1>
                <p class="opacity-75">KAMPUS mengintegrasikan PMB, akademik, keuangan, dan pelaporan dalam satu sistem yang aman, modern, dan mudah digunakan — untuk {{ number_format($stats['mahasiswa'] ?? 0) }} mahasiswa dan {{ number_format($stats['dosen'] ?? 0) }} dosen yang telah terdata.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('pmb.create') }}" class="btn btn-light btn-lg">Mulai Pendaftaran →</a>
                    <a href="{{ route('dokumen.index') }}" class="btn btn-outline-light btn-lg">Dokumentasi</a>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-4 small opacity-75">
                    <span>✓ Hak akses berbasis peran</span><span>✓ Audit trail aktivitas</span><span>✓ QR verifikasi dokumen</span>
                </div>
            </div>
            <div class="col-lg-7">
                {{-- Mockup dashboard ERP (HTML/CSS, data real agregat) --}}
                <div class="kampius-mock" role="img" aria-label="Pratinjau dashboard KAMPUS ERP">
                    <div class="kampius-mock-topbar">
                        <i style="background:#DC4545"></i><i style="background:#E9A23B"></i><i style="background:#16A36A"></i>
                        <span class="ms-2 small text-secondary">KAMPUS — Ringkasan Eksekutif</span>
                        <span class="ms-auto badge bg-green-lt">Live</span>
                    </div>
                    <div class="kampius-mock-body">
                        <div class="kampius-mock-side" aria-hidden="true">
                            <span class="on">▦ Ringkasan</span><span>🎓 Akademik</span><span>💳 Keuangan</span><span>👥 Mahasiswa</span><span>📝 PMB</span><span>📊 Laporan</span><span>⚙ Sistem</span>
                        </div>
                        <div class="kampius-mock-main">
                            <div class="kampius-mock-stats">
                                <div class="kampius-mock-stat"><b>{{ number_format($stats['mahasiswa'] ?? 0) }}</b><small>Mahasiswa</small></div>
                                <div class="kampius-mock-stat"><b>{{ number_format($stats['dosen'] ?? 0) }}</b><small>Dosen</small></div>
                                <div class="kampius-mock-stat"><b>{{ number_format($stats['prodi'] ?? 0) }}</b><small>Prodi</small></div>
                                <div class="kampius-mock-stat"><b>{{ (int) ($stats['krs_pct'] ?? 0) }}%</b><small>KRS disetujui</small></div>
                            </div>
                            <div class="kampius-mock-chart">
                                <small class="text-secondary">Penerimaan terverifikasi · 6 bulan</small>
                                @php
                                    $vals = $stats['bayar_bulanan']['nominal'] ?? [];
                                    $maks = max(1, max($vals ?: [0]));
                                @endphp
                                <div class="kampius-mock-bars mt-1">
                                    @foreach($vals as $v)
                                        <i style="height:{{ max(8, $v / $maks * 100) }}%;background:{{ $loop->last ? '#1769F5' : '#9DB9E8' }}" title="Rp {{ number_format($v, 0, ',', '.') }}"></i>
                                    @endforeach
                                </div>
                            </div>
                            <div class="kampius-mock-rows">
                                <div><span>KRS menunggu persetujuan</span><strong>{{ number_format($stats['krs_pending'] ?? 0) }}</strong></div>
                                <div><span>Mahasiswa baru bulan ini</span><strong>{{ number_format($stats['pmb_baru'] ?? 0) }}</strong></div>
                                <div><span>Kehadiran tercatat</span><strong>{{ (int) ($stats['hadir_pct'] ?? 0) }}%</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ 2b. FOTO KAMPUS (AI WAN 2.1, lokal) ============ --}}
<div class="container-xl mt-4" style="max-width: 1280px">
    <div class="position-relative overflow-hidden" style="border-radius:18px">
        <img src="{{ asset('img/kampus-hero-wan.png') }}" alt="Gedung kampus modern KAMPUS" class="w-100 d-block" style="max-height:420px;object-fit:cover" loading="lazy">
        <div class="position-absolute bottom-0 start-0 end-0 p-3 p-md-4 d-flex flex-wrap align-items-end gap-2" style="background:linear-gradient(transparent, rgba(16,29,75,.82))">
            <div class="text-white me-auto">
                <strong class="d-block">Kampus Modern, Sistem Modern</strong>
                <span class="small opacity-75">Gedung, layanan, dan data akademik dalam satu ekosistem digital.</span>
            </div>
            <a href="{{ route('pmb.create') }}" class="btn btn-light">Daftar Sekarang →</a>
        </div>
    </div>
</div>
{{-- ============ 3. PRODUCT VALUE ============ --}}
<div class="container-xl mt-4" style="max-width: 1280px" id="nilai">
    <div class="row text-center g-3">
        @php
            $values = [
                ['Akademik Terintegrasi', 'KRS, KHS, nilai, kurikulum, dan jadwal dalam satu alur yang konsisten dan teraudit.'],
                ['Keuangan Terkendali', 'Tagihan, VA/QRIS, verifikasi berjenjang, jurnal otomatis, dan rekonsiliasi bank.'],
                ['Siklus Mahasiswa Penuh', 'Dari PMB hingga yudisium, wisuda, dan alumni — tanpa data terpisah.'],
                ['Akses Sesuai Peran', 'Rektor, BAAK, keuangan, dosen, dan mahasiswa melihat tepat yang seharusnya.'],
            ];
        @endphp
        @foreach($values as [$judul, $deskripsi])
            <div class="col-md-3">
                <div class="card kampius-modul"><div class="card-body">
                    <h3 class="h4">{{ $judul }}</h3>
                    <p class="text-secondary small mb-0">{{ $deskripsi }}</p>
                </div></div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ 4. MODULE SHOWCASE ============ --}}
<div class="container-xl mt-5" style="max-width: 1280px" id="modul">
    <div class="mb-3">
        <div class="text-primary small mb-1 fw-bold">MODUL LENGKAP</div>
        <h2>Semua Kebutuhan Kampus, Satu Platform</h2>
        <p class="text-secondary mb-0">Setiap kartu membuka modul aslinya (masuk sesuai peran Anda).</p>
    </div>
    @php
        $moduls = [
            ['🧑‍🎓', 'PMB Online', 'Pendaftaran, seleksi, dan verifikasi calon mahasiswa.', 'pmb.create'],
            ['🎓', 'Akademik & Kurikulum', 'Prodi, kurikulum, mata kuliah, dan jadwal.', 'publik.prodi'],
            ['📝', 'KRS & KHS', 'Pengajuan KRS, persetujuan PA, dan KHS terverifikasi.', 'mahasiswa.krs.pengajuan'],
            ['💳', 'Keuangan & UKT', 'Tagihan massal, VA/QRIS, dan verifikasi berjenjang.', 'keuangan.bayar'],
            ['📚', 'LMS & Absensi', 'Materi, tugas, dan presensi QR per pertemuan.', 'lms.materi'],
            ['📜', 'Surat & Transkrip', 'Penomoran otomatis dan verifikasi QR anti-palsu.', 'surat.pengajuan'],
            ['🎓', 'Skripsi & Sidang', 'Judul, bimbingan, sidang, dan penilaian penguji.', 'skripsi.pengajuan'],
            ['🏅', 'Beasiswa', 'Program, penetapan, dan potongan otomatis tagihan.', 'keuangan.beasiswa'],
            ['🎓', 'Yudisium & Wisuda', 'Kelulusan, SKL, batch wisuda, dan PIN ijazah.', 'skripsi.yudisium'],
            ['👥', 'Alumni & Magang', 'Direktori alumni dan hub lowongan magang.', 'publik.alumni'],
            ['📊', 'Laporan & Funnel', 'Rekap eksekutif dan funnel konversi PMB.', 'dashboard.pimpinan'],
            ['🔌', 'Integrasi & API', 'API v1/v2, webhook HMAC, dan PDDikti.', 'admin.integrasi'],
        ];
    @endphp
    <div class="row row-cards">
        @foreach($moduls as [$ikon, $nama, $deskripsi, $rute])
            <div class="col-md-6 col-lg-4">
                <div class="card kampius-modul"><div class="card-body d-flex gap-3">
                    <span class="kampius-modul-icon bg-blue-lt">{{ $ikon }}</span>
                    <div>
                        <h3 class="card-title mb-1">{{ $nama }}</h3>
                        <p class="text-secondary small mb-1">{{ $deskripsi }}</p>
                        <a href="{{ route($rute) }}" class="small">Buka modul →</a>
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ 5. REAL PRODUCT PREVIEW ============ --}}
<div class="bg-light mt-5">
    <div class="container-xl py-5" style="max-width: 1280px" id="pratinjau">
        <div class="text-primary small mb-1 fw-bold">PRATINJAU PRODUK</div>
        <h2>Lihat Cara Kerjanya</h2>
        <p class="text-secondary">Komposisi antarmuka yang sama persis dengan modul aslinya.</p>
        <div class="row row-cards mt-3">
            <div class="col-lg-6">
                <div class="kampius-mock">
                    <div class="kampius-mock-topbar"><i style="background:#DC4545"></i><i style="background:#E9A23B"></i><i style="background:#16A36A"></i><span class="ms-2 small text-secondary">Keuangan — Verifikasi Pembayaran</span></div>
                    <img src="{{ asset('img/keuangan-wan.png') }}" alt="Visual analitik keuangan KAMPUS" class="w-100 d-block" style="height:140px;object-fit:cover" loading="lazy">
                    <div class="p-3">
                        <div class="d-flex justify-content-between mb-2"><strong>Antrean verifikasi</strong><span class="badge bg-warning-lt">{{ number_format($stats['pembayaran_pending'] ?? 0) }} menunggu</span></div>
                        <div class="kampius-mock-rows">
                            <div><span>Pembayaran terverifikasi (total)</span><strong>Rp {{ number_format((float) array_sum($stats['bayar_bulanan']['nominal'] ?? []), 0, ',', '.') }}</strong></div>
                            <div><span>Tingkat pelunasan</span><strong>{{ (int) ($stats['lunas_pct'] ?? 0) }}%</strong></div>
                            <div><span>Piutang aktif</span><strong>Rp {{ number_format($stats['piutang'] ?? 0, 0, ',', '.') }}</strong></div>
                        </div>
                        <a href="{{ route('keuangan.verifikasi') }}" class="btn btn-primary btn-sm mt-2">Buka verifikasi →</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="kampius-mock">
                    <div class="kampius-mock-topbar"><i style="background:#DC4545"></i><i style="background:#E9A23B"></i><i style="background:#16A36A"></i><span class="ms-2 small text-secondary">Akademik — Alur KRS</span></div>
                    <div class="p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-blue">1 Pengajuan</span><span class="text-secondary">→</span>
                            <span class="badge bg-warning-lt">2 Persetujuan PA</span><span class="text-secondary">→</span>
                            <span class="badge bg-green-lt">3 {{ (int) ($stats['krs_pct'] ?? 0) }}% disetujui</span>
                        </div>
                        <div class="kampius-mock-rows">
                            <div><span>KRS menunggu hari ini</span><strong>{{ number_format($stats['krs_pending'] ?? 0) }}</strong></div>
                            <div><span>Disetujui hari ini</span><strong>{{ number_format($stats['krs_ok'] ?? 0) }}</strong></div>
                            <div><span>Kehadiran tercatat</span><strong>{{ (int) ($stats['hadir_pct'] ?? 0) }}%</strong></div>
                        </div>
                        <a href="{{ route('mahasiswa.krs.pengajuan') }}" class="btn btn-primary btn-sm mt-2">Buka KRS →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ 6. LIFECYCLE ============ --}}
<div class="container-xl mt-5" style="max-width: 1280px">
    <div class="text-primary small mb-1 fw-bold">SIKLUS HIDUP</div>
    <h2>Dari Pendaftar hingga Alumni</h2>
    <div class="row text-center mt-3 g-3">
        @php
            $siklus = [['PMB', 'Pendaftaran online'], ['Seleksi', 'Verifikasi & nilai'], ['Daftar Ulang', 'NIM & tagihan awal'], ['KRS', 'Rencana studi'], ['Kuliah', 'LMS & absensi'], ['Nilai', 'KHS & IPK'], ['Skripsi', 'Sidang & lulus'], ['Wisuda', 'Yudisium & ijazah'], ['Alumni', 'Tracer & magang']];
        @endphp
        @foreach($siklus as [$judul, $sub])
            <div class="col-4 col-md-2 position-relative">
                @if(!$loop->last)<span class="kampius-step-line d-none d-md-block"></span>@endif
                <span class="kampius-step-num">{{ $loop->iteration }}</span>
                <div><strong class="small">{{ $judul }}</strong></div>
                <div class="text-secondary small">{{ $sub }}</div>
            </div>
        @endforeach
    </div>
    <div class="card kampius-card mt-3"><div class="row g-0 align-items-center">
        <div class="col-md-6"><img src="{{ asset('img/wisuda-wan.png') }}" alt="Wisuda kampus KAMPUS" class="img-fluid w-100 h-100" style="object-fit:cover;min-height:220px;border-radius:16px 0 0 16px" loading="lazy"></div>
        <div class="col-md-6 p-4">
            <h3 class="mb-1">Dari Pendaftar hingga Wisudawan</h3>
            <p class="text-secondary">Satu alur utuh tanpa data terpisah — dan setiap dokumen penting terverifikasi QR.</p>
            <a href="{{ route('pmb.create') }}" class="btn btn-primary">Mulai dari PMB →</a>
        </div>
    </div></div>
</div>

{{-- ============ 7. ROLE EXPERIENCE ============ --}}
<div class="container-xl mt-5" style="max-width: 1280px" id="peran">
    <div class="text-primary small mb-1 fw-bold">UNTUK SEMUA PERAN</div>
    <h2>Satu Sistem, Pengalaman yang Tepat</h2>
    <img src="{{ asset('img/akademik-wan.png') }}" alt="Mahasiswa belajar dengan laptop di kampus" class="img-fluid w-100 mb-3" style="max-height:280px;object-fit:cover;border-radius:16px" loading="lazy">
    <div class="row row-cards mt-3">
        @php
            $perans = [
                ['Rektor', 'Pertumbuhan, kinerja akademik, dan keuangan institusi.', 'dashboard.pimpinan'],
                ['BAAK', 'Administrasi mahasiswa, KRS, dan kalender akademik.', 'admin.pmb.verifikasi'],
                ['Keuangan', 'Verifikasi pembayaran dan laporan penerimaan.', 'keuangan.verifikasi'],
                ['Dosen', 'Jadwal mengajar, roster, dan input nilai.', 'dosen.krs.persetujuan'],
                ['Mahasiswa', 'KRS, jadwal, nilai, dan tagihan dalam satu tempat.', 'mahasiswa.krs.pengajuan'],
                ['Admin Prodi', 'Mahasiswa, kurikulum, dan persetujuan program studi.', 'admin.master'],
            ];
        @endphp
        @foreach($perans as [$nama, $deskripsi, $rute])
            <div class="col-md-4">
                <div class="card kampius-role"><div class="card-body">
                    <h3 class="card-title">{{ $nama }}</h3>
                    <p class="text-secondary small">{{ $deskripsi }}</p>
                    <a href="{{ route($rute) }}" class="small">Masuk sebagai {{ $nama }} →</a>
                </div></div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ 8. SECURITY + INTEGRATION ============ --}}
<div class="container-xl mt-5" style="max-width: 1280px" id="keamanan">
    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card h-100 bg-dark text-white overflow-hidden">
                <img src="{{ asset('img/keamanan-wan.png') }}" alt="Perisai keamanan siber KAMPUS" class="w-100 d-block" style="height:150px;object-fit:cover" loading="lazy">
                <div class="card-body">
                    <span class="badge bg-dark-lt text-info mb-2">KEAMANAN TERVERIFIKASI</span>
                    <h2 class="text-white">Keamanan Tingkat Enterprise</h2>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2">✓ Hak akses berbasis peran di setiap rute</li>
                        <li class="mb-2">✓ Otorisasi kepemilikan data per pengguna</li>
                        <li class="mb-2">✓ Dokumen QR bertanda tangan anti-palsu</li>
                        <li class="mb-2">✓ Audit trail setiap aksi penting</li>
                        <li class="mb-2">✓ Webhook HMAC dan throttle anti-abuse</li>
                    </ul>
                    <a href="{{ route('dokumen.index') }}" class="btn btn-outline-light">Baca dokumentasi →</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <span class="badge bg-blue-lt text-primary mb-2">INTEGRASI</span>
                    <h2>Terhubung ke Ekosistem Kampus</h2>
                    <ul class="list-unstyled mt-3 text-secondary">
                        <li class="mb-2">→ Payment gateway: VA bank, QRIS, e-wallet</li>
                        <li class="mb-2">→ API v1 untuk aplikasi mobile</li>
                        <li class="mb-2">→ API v2 publik + webhook terverifikasi</li>
                        <li class="mb-2">→ Ekspor PDDikti dan laporan Excel</li>
                        <li class="mb-2">→ Kalender akademik + format ICS</li>
                    </ul>
                    <a href="{{ route('admin.integrasi') }}" class="btn btn-primary">Kelola integrasi →</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ 9. FAQ ============ --}}
<div class="container-xl mt-5" style="max-width: 860px" id="faq">
    <div class="text-center">
        <span class="badge bg-blue-lt text-primary mb-2">FAQ</span>
        <h2>Pertanyaan yang Sering Diajukan</h2>
    </div>
    <div class="accordion mt-3" id="faqAccordion">
        @php
            $faqs = [
                ['Bagaimana cara mencoba KAMPUS?', 'Klik Demo Gratis dan masuk dengan akun demo sesuai peran (mahasiswa, dosen, keuangan, admin). Setiap peran melihat menu dan datanya sendiri.'],
                ['Apakah data saya aman dan terisolasi?', 'Ya. Setiap rute dilindungi peran dan kepemilikan data dicek di server — mahasiswa hanya melihat datanya sendiri, dosen hanya bimbingannya.'],
                ['Bagaimana pembayaran kuliah dilakukan?', 'Mahasiswa membuat VA/QRIS dari tagihannya, atau mengunggah bukti manual untuk diverifikasi petugas keuangan dengan jurnal otomatis.'],
                ['Apakah dokumen ijazah dan transkrip bisa diverifikasi?', 'Ya. Setiap dokumen membawa QR bertanda tangan digital yang bisa dicek siapa pun lewat halaman verifikasi publik.'],
                ['Apakah mendukung banyak program studi?', 'Ya. Struktur Fakultas → Program Studi mendukung banyak prodi dalam satu instalasi, dengan rekap per prodi.'],
            ];
        @endphp
        @foreach($faqs as $i => [$tanya, $jawab])
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">{{ $tanya }}</button>
                </h2>
                <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">{{ $jawab }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- ============ 10. CTA ============ --}}
<div class="kampius-landing-hero text-white mt-5">
    <div class="container-xl py-5 text-center position-relative" style="max-width: 1280px; z-index:1">
        <h2 class="fw-bold">Siap Mengelola Kampus dengan KAMPUS?</h2>
        <p class="opacity-75 mb-3">Mulai dari PMB online hari ini — tanpa instalasi rumit.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="{{ route('pmb.create') }}" class="btn btn-light btn-lg">Daftar PMB →</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Demo Gratis</a>
        </div>
    </div>
</div>

{{-- ============ 11. FOOTER ============ --}}
<footer class="container-xl mt-4 mb-3" style="max-width: 1280px" id="kontak">
    <div class="d-flex flex-wrap justify-content-between align-items-center text-secondary small gap-2">
        <span class="d-inline-flex align-items-center gap-2"><img src="{{ asset('img/logo-kampus-mark.png') }}" alt="Logo KAMPUS" width="22" height="22" style="border-radius:6px"><strong class="text-dark">KAMPUS</strong> <span class="text-secondary">University ERP</span></span>
        <span>
            <a href="{{ route('home') }}" class="me-3">Beranda</a>
            <a href="#modul" class="me-3">Modul</a>
            <a href="{{ route('dokumen.index') }}" class="me-3">Dokumentasi</a>
            <a href="#faq" class="me-3">FAQ</a>
            <a href="#kontak">Kontak</a>
        </span>
        <span>© {{ date('Y') }} KAMPUS. Data agregat di halaman ini dihitung langsung dari database.</span>
    </div>
</footer>
@endsection
