@extends('layouts.tabler')

@section('title', 'Dashboard - '.config('app.name', 'KAMPIUS'))
@section('pretitle', date('l, d M Y'))
@section('page-title', 'Ringkasan')

@section('breadcrumb')
    <ol class="breadcrumb" aria-label="Breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">Ringkasan</li>
    </ol>
@endsection

@push('styles')
<style>
    .kampius-hero {
        border: 1px solid #E8EEF7;
        border-radius: 18px;
        background: linear-gradient(120deg, #10234B 0%, #1046A0 60%, #1769F5 100%);
        color: #fff;
        overflow: hidden;
        position: relative;
    }
    .kampius-hero::after {
        content: "";
        position: absolute;
        right: -80px; top: -80px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(17,197,232,.25);
    }
    .kampius-hero::before {
        content: "";
        position: absolute;
        right: 120px; bottom: -110px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.08);
    }
    .kampius-hero .role-badge {
        background: rgba(255,255,255,.16);
        border-radius: 999px;
        padding: .25rem .8rem;
        font-size: .75rem;
    }
    .kampius-metric {
        border: 1px solid #E8EEF7;
        border-radius: 16px;
        background: #fff;
        transition: box-shadow .2s ease, transform .2s ease;
        height: 100%;
    }
    .kampius-metric:hover { box-shadow: 0 12px 28px rgba(23,105,245,.12); transform: translateY(-2px); }
    .kampius-metric .m-label { color: var(--kampius-muted, #718096); font-size: .82rem; }
    .kampius-metric .m-value { color: #10234B; font-weight: 700; font-size: 1.8rem; line-height: 1.15; }
    .kampius-metric .m-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .kampius-strip {
        border: 1px solid #E8EEF7;
        border-radius: 14px;
        background: #fff;
    }
    .kampius-funnel-bar { height: 10px; border-radius: 999px; background: #EDF1F7; overflow: hidden; }
    .kampius-funnel-bar > div { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #1769F5, #11C5E8); }
    .kampius-queue-num {
        width: 34px; height: 34px; border-radius: 10px;
        background: rgba(23,105,245,.1); color: #1046A0;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; flex-shrink: 0;
    }
    @media (max-width: 768px) {
        .kampius-metric .m-value { font-size: 1.45rem; }
    }
</style>
@endpush

@section('content')
@php
    $hour = (int) now()->format('H');
    $salam = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    $peran = auth()->user()->roles->pluck('name')->join(', ');
    $isMhs = auth()->user()->hasRole('mahasiswa') && ! auth()->user()->hasAnyRole(['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'pimpinan']);
    $isDosen = auth()->user()->hasRole('dosen') && ! auth()->user()->hasAnyRole(['mahasiswa', 'superadmin', 'admin_prodi']);
    $heroMetrics = [
        ['label' => 'Total Mahasiswa', 'value' => number_format($stats['mahasiswa'] ?? 0), 'sub' => ($stats['mahasiswa_aktif'] ?? 0).' aktif', 'bg' => 'rgba(23,105,245,.12)', 'fg' => '#1769F5', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'route' => 'mahasiswa.krs.pengajuan'],
        ['label' => 'Mahasiswa Aktif', 'value' => number_format($stats['mahasiswa_aktif'] ?? 0), 'sub' => '+'.($stats['mahasiswa_baru'] ?? 0).' bulan ini', 'bg' => 'rgba(22,163,106,.12)', 'fg' => '#16A36A', 'icon' => 'M22 11.1V12a10 10 0 1 1-5.9-9.1', 'route' => 'mahasiswa.krs.pengajuan'],
        ['label' => 'Mahasiswa Baru', 'value' => number_format($stats['mahasiswa_baru'] ?? 0), 'sub' => 'bulan berjalan', 'bg' => 'rgba(17,197,232,.14)', 'fg' => '#0B93B0', 'icon' => 'M12 5v14M5 12h14', 'route' => 'admin.pmb.verifikasi'],
        ['label' => 'Total Dosen', 'value' => number_format($stats['dosen_aktif'] ?? $stats['dosen'] ?? 0), 'sub' => 'aktif mengajar', 'bg' => 'rgba(233,162,59,.16)', 'fg' => '#B97A12', 'icon' => 'M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5', 'route' => 'dosen.krs.persetujuan'],
        ['label' => 'PMB Pending', 'value' => number_format($stats['pmb_pending'] ?? 0), 'sub' => 'menunggu verifikasi', 'bg' => 'rgba(23,105,245,.12)', 'fg' => '#1769F5', 'icon' => 'M8 7V3m8 4V3M3 11h18', 'route' => 'admin.pmb.verifikasi'],
        ['label' => 'Outstanding', 'value' => 'Rp '.number_format(($stats['outstanding'] ?? 0) / 1000000, 1, ',', '.').' jt', 'sub' => 'tagihan aktif', 'bg' => 'rgba(220,69,69,.12)', 'fg' => '#DC4545', 'icon' => 'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z', 'route' => 'keuangan.verifikasi'],
    ];
    $funnelOrder = ['menunggu', 'diajukan', 'diverifikasi', 'lulus'];
    $funnelTotal = max(1, array_sum($funnel ?? []));
    $funnelMaks = max(1, max(array_merge([0], array_values($funnel ?? []))));
    $statusColors = ['aktif' => '#1769F5', 'cuti' => '#E9A23B', 'nonaktif' => '#77849A', 'do' => '#DC4545', 'keluar' => '#5B6B82', 'lulus' => '#16A36A'];
    $statusTotal = max(1, array_sum($statusBreakdown ?? []));
@endphp

{{-- 1. GREETING HERO --}}
<div class="kampius-hero p-4 p-lg-5 mb-3 position-relative">
    <div class="position-relative" style="z-index:1">
        <span class="role-badge">{{ $peran }}</span>
        <h2 class="mt-2 mb-1 fw-bold">{{ $salam }}, {{ auth()->user()->name }}!</h2>
        <p class="mb-0 opacity-75">{{ now()->translatedFormat('l, d F Y') }} · Tahun akademik {{ $periodeAktif ?? '—' }}</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            @if($isMhs)
                <a href="{{ route('mahasiswa.krs.pengajuan') }}" class="btn btn-light">Pengajuan KRS</a>
                <a href="{{ route('keuangan.bayar') }}" class="btn btn-outline-light">Bayar Tagihan</a>
            @elseif($isDosen)
                <a href="{{ route('dosen.krs.persetujuan') }}" class="btn btn-light">Persetujuan KRS</a>
                <a href="{{ route('nilai.input') }}" class="btn btn-outline-light">Input Nilai</a>
            @endif
            @can('keuangan.manage')
                <a href="{{ route('keuangan.verifikasi') }}" class="btn btn-light">Verifikasi ({{ $stats['verifikasi_pending'] ?? 0 }})</a>
            @endcan
            @can('pmb.verify')
                <a href="{{ route('admin.pmb.verifikasi') }}" class="btn btn-outline-light">Verifikasi PMB ({{ $stats['pmb_pending'] ?? 0 }})</a>
            @endcan
        </div>
    </div>
</div>

{{-- 2. HERO METRICS --}}
<div class="row row-deck row-cards mb-3">
    @foreach($heroMetrics as $m)
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="card kampius-metric">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="min-w-0">
                            <div class="m-label">{{ $m['label'] }}</div>
                            <div class="m-value mt-1">{{ $m['value'] }}</div>
                            <div class="small kampius-muted mt-1">{{ $m['sub'] }}</div>
                        </div>
                        <span class="m-icon" style="background:{{ $m['bg'] }};color:{{ $m['fg'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $m['icon'] }}"/></svg>
                        </span>
                    </div>
                    @php try { $murl = route($m['route']); } catch (Throwable) { $murl = '#'; } @endphp
                    <a href="{{ $murl }}" class="small mt-2 d-inline-block">Lihat detail →</a>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- 3. INSTITUTION STRIP --}}
<div class="kampius-strip px-3 py-2 mb-3 d-flex flex-wrap align-items-center gap-x-4 gap-y-1">
    <span class="fw-bold">Kampius University</span>
    <span class="kampius-muted">•</span><span><strong>{{ number_format($stats['prodi'] ?? 0) }}</strong> Program Studi</span>
    <span class="kampius-muted">•</span><span><strong>{{ number_format($stats['matakuliah'] ?? 0) }}</strong> Mata Kuliah</span>
    <span class="kampius-muted">•</span><span><strong>{{ number_format($stats['dosen'] ?? 0) }}</strong> Dosen</span>
    <a href="{{ route('publik.prodi') }}" class="ms-auto small">Jelajahi prodi →</a>
</div>

{{-- 4. GROWTH + STATUS --}}
<div class="row row-deck row-cards">
    <div class="col-lg-8">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Pertumbuhan Mahasiswa <span class="kampius-muted fw-normal">• per angkatan</span></h3>
                <span class="badge bg-blue-lt">Real data</span>
            </div>
            <div class="card-body">
                @if(!empty($angkatanBreakdown))
                    @php $maksAng = max(1, max($angkatanBreakdown)); @endphp
                    <div class="d-flex align-items-end gap-3" style="height:210px">
                        @foreach($angkatanBreakdown as $angk => $tot)
                            <div class="flex-fill text-center h-100 d-flex flex-column justify-content-end">
                                <strong>{{ number_format($tot) }}</strong>
                                <div title="{{ $angk }}: {{ number_format($tot) }} mahasiswa" style="height:{{ max(4, $tot / $maksAng * 100) }}%;background:linear-gradient(180deg,#1769F5,#11C5E8);border-radius:8px 8px 4px 4px;min-height:6px"></div>
                                <div class="small kampius-muted mt-1">{{ $angk }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Belum ada data angkatan</h3><p class="kampius-muted mb-0">Distribusi muncul setelah data mahasiswa diimpor.</p></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Status Mahasiswa</h3></div>
            <div class="card-body">
                @if(!empty($statusBreakdown))
                    @php
                        $palette = array_values($statusColors); $i = 0; $off = 25; $segs = [];
                        foreach ($statusBreakdown as $st => $tot) {
                            $pct = $tot / $statusTotal * 100;
                            $segs[] = ['label' => $st, 'tot' => $tot, 'pct' => $pct, 'color' => $statusColors[$st] ?? $palette[$i % count($palette)], 'off' => $off];
                            $off += $pct; $i++;
                        }
                    @endphp
                    <div class="text-center">
                        <svg viewBox="0 0 42 42" style="width:150px;height:150px" role="img" aria-label="Distribusi status mahasiswa">
                            <circle cx="21" cy="21" r="15.9" fill="none" stroke="#EEF1F6" stroke-width="5"/>
                            @foreach($segs as $s)
                                <circle cx="21" cy="21" r="15.9" fill="none" stroke="{{ $s['color'] }}" stroke-width="5" stroke-dasharray="{{ round($s['pct'],1) }} {{ round(100 - $s['pct'],1) }}" stroke-dashoffset="{{ round($s['off'],1) }}" stroke-linecap="round"/>
                            @endforeach
                            <text x="21" y="20" text-anchor="middle" font-size="6" font-weight="bold" fill="#10234B">{{ number_format($statusTotal) }}</text>
                            <text x="21" y="24.5" text-anchor="middle" font-size="2.4" fill="#77849A">Mahasiswa</text>
                        </svg>
                    </div>
                    <div class="mt-2">
                        @foreach($segs as $s)
                            <div class="d-flex justify-content-between small mb-1">
                                <span><span class="d-inline-block me-1" style="width:10px;height:10px;border-radius:3px;background:{{ $s['color'] }}"></span>{{ ucfirst($s['label']) }} ({{ number_format($s['pct'],1) }}%)</span>
                                <strong>{{ number_format($s['tot']) }}</strong>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Belum ada data status</h3></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 5. COLLECTION + FUNNEL --}}
<div class="row row-deck row-cards mt-3">
    <div class="col-lg-7">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Arus Kas <span class="kampius-muted fw-normal">• pemasukan vs tagihan terbit</span></h3>
            </div>
            <div class="card-body">
                @php $maksBar = max(1, max($chartNominal ?? [0]), max($chartTagihan ?? [0])); @endphp
                @if(array_sum($chartNominal ?? []) + array_sum($chartTagihan ?? []) > 0)
                    <div class="d-flex align-items-end justify-content-between gap-2" style="height:190px">
                        @foreach($chartLabels as $i => $lb)
                            @php $v1 = ($chartNominal[$i] ?? 0) / $maksBar * 100; $v2 = ($chartTagihan[$i] ?? 0) / $maksBar * 100; @endphp
                            <div class="flex-fill text-center">
                                <div class="d-flex justify-content-center gap-1 align-items-end" style="height:150px">
                                    <div title="Tagihan Rp {{ number_format($chartTagihan[$i] ?? 0, 0, ',', '.') }}" style="width:12px;height:{{ max(3,$v2) }}%;background:#16A36A;border-radius:4px"></div>
                                    <div title="Masuk Rp {{ number_format($chartNominal[$i] ?? 0, 0, ',', '.') }}" style="width:12px;height:{{ max(3,$v1) }}%;background:#1769F5;border-radius:4px"></div>
                                </div>
                                <div class="small kampius-muted mt-1">{{ $lb }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-3 small mt-2"><span><span class="badge bg-blue" style="width:10px;height:10px;padding:0">&nbsp;</span> Pemasukan</span><span><span class="badge bg-green" style="width:10px;height:10px;padding:0">&nbsp;</span> Tagihan terbit</span></div>
                @else
                    <div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Belum ada transaksi</h3><p class="kampius-muted mb-0">Pemasukan terverifikasi akan tampil di sini.</p></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Konversi PMB</h3>
                <a href="{{ route('pmb.funnel') }}" class="small">Detail funnel →</a>
            </div>
            <div class="card-body">
                @if(!empty($funnel) && array_sum($funnel) > 0)
                    @foreach($funnelOrder as $tahap)
                        @php $n = $funnel[$tahap] ?? null; @endphp
                        @if($n !== null)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1"><span class="text-capitalize fw-bold">{{ $tahap }}</span><span><strong>{{ number_format($n) }}</strong> <span class="kampius-muted">({{ number_format($n / $funnelTotal * 100, 1) }}%)</span></span></div>
                                <div class="kampius-funnel-bar"><div style="width:{{ max(2, $n / $funnelMaks * 100) }}%"></div></div>
                            </div>
                        @endif
                    @endforeach
                    @php $gugur = ($funnel['ditolak'] ?? 0) + ($funnel['tidak'] ?? 0); @endphp
                    @if($gugur > 0)
                        <div class="small kampius-muted">Gugur/ditolak: <strong>{{ number_format($gugur) }}</strong></div>
                    @endif
                @else
                    <div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Belum ada data PMB</h3><p class="kampius-muted mb-0">Funnel muncul setelah ada pendaftar.</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 6. ACADEMIC PERFORMANCE + MESSAGES --}}
<div class="row row-deck row-cards mt-3">
    <div class="col-lg-7">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Performa Akademik <span class="kampius-muted fw-normal">• % KRS disetujui per semester</span></h3>
                <span class="badge bg-green-lt">Real data</span>
            </div>
            <div class="card-body">
                @if(!empty($perfValues) && array_sum($perfValues) > 0)
                    @php
                        $w = 100; $h = 32; $n = count($perfValues); $pts = [];
                        foreach ($perfValues as $i => $v) {
                            $x = 4 + $i * (($w - 8) / max(1, $n - 1));
                            $y = $h - 4 - ($v / 100) * ($h - 10);
                            $pts[] = [round($x,1), round($y,1)];
                        }
                        $line = implode(' ', array_map(fn($p) => $p[0].','.$p[1], $pts));
                    @endphp
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" style="height:190px" role="img" aria-label="Grafik performa akademik">
                        @for($g = 0; $g <= 4; $g++)
                            @php $gy = 4 + $g * (($h - 8) / 4); @endphp
                            <line x1="4" y1="{{ $gy }}" x2="96" y2="{{ $gy }}" stroke="#EDF1F7" stroke-width="0.3"/>
                            <text x="0" y="{{ $gy + 1 }}" font-size="2.4" fill="#9AA4B5">{{ 100 - $g * 25 }}%</text>
                        @endfor
                        <polygon points="4,{{ $h - 4 }} {{ $line }} 96,{{ $h - 4 }}" fill="rgba(22,163,106,.12)"></polygon>
                        <polyline points="{{ $line }}" fill="none" stroke="#16A36A" stroke-width="0.8" stroke-linejoin="round"/>
                        @foreach($pts as $p)
                            <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="1" fill="#fff" stroke="#16A36A" stroke-width="0.6"/>
                        @endforeach
                    </svg>
                    <div class="d-flex justify-content-between small kampius-muted mt-1 flex-wrap gap-1">
                        @foreach($perfLabels as $i => $lb)
                            <span class="text-center">{{ $lb }}<br><strong class="text-dark">{{ $perfValues[$i] }}%</strong></span>
                        @endforeach
                    </div>
                @else
                    <div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3 class="empty-title">Belum ada data performa</h3><p class="kampius-muted mb-0">Data muncul setelah ada KRS pada tahun akademik.</p></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between"><h3 class="card-title">Pengumuman</h3><a href="{{ route('publik.pengumuman') }}" class="small">Lihat semua</a></div>
            <div class="list-group list-group-flush">
                @forelse($messages as $m)
                    <a href="{{ route('publik.pengumuman.detail', $m->id) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between gap-2"><strong class="text-truncate">{{ $m->judul }}</strong><span class="small kampius-muted text-nowrap">{{ $m->published_at?->format('d M') }}</span></div>
                        <div class="small kampius-muted text-truncate">{{ strip_tags($m->isi) }}</div>
                    </a>
                @empty
                    <div class="list-group-item kampius-muted">Belum ada pengumuman.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- 7. QUEUES: agenda / KRS / tagihan --}}
<div class="row row-deck row-cards mt-3">
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between"><h3 class="card-title">Agenda Terdekat</h3><a href="{{ route('kalender.index') }}" class="small">Kalender →</a></div>
            <div class="list-group list-group-flush">
                @forelse($agenda as $ag)
                    <div class="list-group-item d-flex gap-2 align-items-start">
                        <span class="badge bg-blue-lt text-nowrap">{{ $ag['tanggal'] }}</span>
                        <div class="min-w-0"><strong class="d-block text-truncate">{{ $ag['judul'] }}</strong><span class="small kampius-muted">{{ $ag['sub'] }}</span></div>
                    </div>
                @empty
                    <div class="list-group-item kampius-muted">Belum ada agenda terjadwal.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-start"><div><h3 class="card-title mb-0">Antrean KRS</h3>
                @if($isDosen || auth()->user()->hasAnyRole(['superadmin','admin_prodi']))
                    <div class="small kampius-muted">Bimbingan Anda</div>
                @endif</div>
                @if($isDosen || auth()->user()->hasAnyRole(['superadmin','admin_prodi']))
                    <a href="{{ route('dosen.krs.persetujuan') }}" class="small">Persetujuan →</a>
                @else
                    <a href="{{ route('mahasiswa.krs.pengajuan') }}" class="small">Pengajuan →</a>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @if($isDosen || auth()->user()->hasAnyRole(['superadmin','admin_prodi']))
                    @forelse($krsAntrean as $i => $k)
                        <div class="list-group-item d-flex gap-2 align-items-center">
                            <span class="kampius-queue-num">{{ $i + 1 }}</span>
                            <div class="min-w-0"><strong class="d-block text-truncate">{{ $k->mahasiswa->user->name ?? 'Mhs #'.$k->mahasiswa_id }}</strong><span class="small kampius-muted">KRS #{{ $k->id }} • {{ $k->updated_at?->diffForHumans() }}</span></div>
                        </div>
                    @empty
                        <div class="list-group-item kampius-muted">Tidak ada pengajuan menunggu.</div>
                    @endforelse
                @else
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between"><span>Status KRS saya</span><strong>{{ $saya['krs_status'] ?? '—' }}</strong></div>
                        <a href="{{ route('mahasiswa.krs.pengajuan') }}" class="btn btn-sm btn-outline-primary mt-2">Buka pengajuan</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @if(auth()->user()->hasAnyRole(['keuangan','superadmin','admin_prodi','pimpinan']))
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between"><h3 class="card-title">Tagihan Menunggak Teratas</h3><a href="{{ route('keuangan.verifikasi') }}" class="small">Verifikasi →</a></div>
            <div class="list-group list-group-flush">
                @forelse($tunggakanTop as $t)
                    <div class="list-group-item d-flex justify-content-between gap-2">
                        <div class="min-w-0"><strong class="d-block text-truncate">{{ $t->mahasiswa->user->name ?? 'Mhs #'.$t->mahasiswa_id }}</strong><span class="small kampius-muted">{{ $t->jenis }}</span></div>
                        <strong class="text-nowrap">Rp {{ number_format($t->sisa(), 0, ',', '.') }}</strong>
                    </div>
                @empty
                    <div class="list-group-item kampius-muted">Tidak ada tunggakan.</div>
                @endforelse
            </div>
        </div>
        @elseif($isMhs)
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Tagihan Saya</h3></div>
            <div class="card-body text-center py-4">
                <div class="h1 mb-1">Rp {{ number_format($saya['outstanding'] ?? 0, 0, ',', '.') }}</div>
                <p class="kampius-muted small">{{ $saya['tagihan_aktif'] ?? 0 }} tagihan aktif</p>
                <a href="{{ route('keuangan.bayar') }}" class="btn btn-primary">Bayar Sekarang</a>
            </div>
        </div>
        @else
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Status KRS</h3></div>
            <div class="card-body">
                @forelse($krsStatus as $status => $total)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-{{ $status === 'disetujui' ? 'success' : ($status === 'diajukan' ? 'warning' : 'secondary') }}">{{ $status }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p class="kampius-muted mb-0">Belum ada KRS.</p>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</div>

{{-- 8. ACTIVITY + QUICK ACTIONS --}}
<div class="row row-cards mt-3">
    <div class="col-lg-8">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Aktivitas Terakhir</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($aktivitas as $a)
                            <tr><td class="text-nowrap">{{ $a->created_at?->format('d M H:i') }}</td><td>{{ $a->user->name ?? 'sistem' }}</td><td>{{ $a->action }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="kampius-muted">Belum ada aktivitas tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Aksi Cepat</h3></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @can('pmb.verify')
                        <a href="{{ route('admin.pmb.verifikasi') }}" class="btn btn-outline-primary">Verifikasi PMB</a>
                    @endcan
                    @if($isDosen || auth()->user()->hasAnyRole(['superadmin','admin_prodi']))
                        <a href="{{ route('nilai.input') }}" class="btn btn-outline-primary">Input Nilai</a>
                    @endif
                    @can('keuangan.manage')
                        <a href="{{ route('keuangan.verifikasi') }}" class="btn btn-outline-primary">Verifikasi Bayar</a>
                    @endcan
                    <a href="{{ route('skripsi.pengajuan') }}" class="btn btn-outline-primary">Skripsi</a>
                    <a href="{{ route('kalender.index') }}" class="btn btn-outline-primary">Kalender</a>
                    <a href="{{ route('helpdesk.index') }}" class="btn btn-outline-primary">Helpdesk</a>
                </div>
                @isset($locks)
                    <div class="mt-3 d-flex flex-wrap gap-1">
                        @foreach($locks as $kunci => $buka)
                            <span class="badge bg-{{ $buka ? 'success' : 'danger' }}-lt">{{ $kunci }}: {{ $buka ? 'buka' : 'tutup' }}</span>
                        @endforeach
                    </div>
                @endisset
                <hr>
                <h4 class="small text-uppercase kampius-muted">Status KRS</h4>
                @forelse($krsStatus as $status => $total)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-{{ $status === 'disetujui' ? 'success' : ($status === 'diajukan' ? 'warning' : 'secondary') }}">{{ $status }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p class="kampius-muted mb-0">Belum ada KRS.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
