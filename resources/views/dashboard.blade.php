@extends('layouts.tabler')

@section('title', 'Dashboard - '.config('app.name', 'KAMPIUS'))
@section('pretitle', date('l, d M Y'))
@section('page-title', 'Dashboard')

@push('styles')
<style>
    :root {
        --kampius-primary: #1769F5;
        --kampius-dark: #1046A0;
        --kampius-navy: #10234B;
        --kampius-cyan: #11C5E8;
        --kampius-bg: #F5F8FD;
        --kampius-text: #172B4D;
        --kampius-muted: #718096;
    }
    .kampius-stat {
        border: 1px solid #E8EEF7;
        border-radius: 16px;
        background: #fff;
        transition: box-shadow .2s ease, transform .2s ease;
    }
    .kampius-stat:hover { box-shadow: 0 12px 28px rgba(23,105,245,.12); transform: translateY(-2px); }
    .kampius-stat .label { color: var(--kampius-muted); font-size: .85rem; }
    .kampius-stat .value { color: var(--kampius-navy); font-weight: 700; font-size: 1.9rem; line-height: 1.1; }
    .kampius-go {
        width: 44px; height: 44px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(23,105,245,.1); color: var(--kampius-primary);
    }
    .kampius-card { border: 1px solid #E8EEF7; border-radius: 16px; }
    .kampius-muted { color: var(--kampius-muted); }
    .bar-track { background: #EEF3FB; border-radius: 6px; }
    .donut-legend span.dot { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
    @media (max-width: 768px) {
        .kampius-stat .value { font-size: 1.5rem; }
    }
</style>
@endpush

@section('page-actions')
    <span class="badge bg-blue-lt">Tahun Aktif: {{ date('Y') }}/{{ date('Y') + 1 }}</span>
@endsection

@section('content')
{{-- STAT CARDS ala referensi: Mahasiswa / Dosen / Program Studi / Pendapatan --}}
<div class="row row-deck row-cards mb-3">
    @php
        $statCards = [
            ['label' => 'Mahasiswa', 'sub' => ($stats['mahasiswa_aktif'] ?? 0).' aktif • +'.($stats['mahasiswa_baru'] ?? 0).' bulan ini', 'value' => number_format($stats['mahasiswa'] ?? 0), 'route' => 'mahasiswa.krs.pengajuan'],
            ['label' => 'Dosen', 'sub' => ($stats['dosen_aktif'] ?? 0).' aktif mengajar', 'value' => number_format($stats['dosen_aktif'] ?? $stats['dosen'] ?? 0), 'route' => 'dosen.krs.persetujuan'],
            ['label' => 'Program Studi', 'sub' => ($stats['matakuliah'] ?? 0).' matakuliah • '.($stats['krs_pending'] ?? 0).' KRS menunggu', 'value' => number_format($stats['prodi'] ?? 0), 'route' => 'publik.prodi'],
            ['label' => 'Pendapatan', 'sub' => 'Rp '.number_format($stats['pendapatan_bulan_ini'] ?? 0, 0, ',', '.').' bulan ini', 'value' => 'Rp '.number_format(($stats['pendapatan_total'] ?? 0) / 1000000, 1, ',', '.').' jt', 'route' => 'laporan.index'],
        ];
    @endphp
    @foreach($statCards as $c)
        <div class="col-sm-6 col-lg-3">
            <div class="card kampius-stat">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">{{ $c['label'] }}</div>
                        <div class="value mt-1">{{ $c['value'] }}</div>
                        <div class="small kampius-muted mt-1">{{ $c['sub'] }}</div>
                    </div>
                    @php try { $url = route($c['route']); } catch (Throwable) { $url = '#'; } @endphp
                    <a href="{{ $url }}" class="kampius-go" aria-label="Buka {{ $c['label'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Operational strip: outstanding, verifikasi, PMB (role-aware) --}}
<div class="row row-cards mb-3">
    @if(auth()->user()->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi', 'pimpinan']))
    <div class="col-md-4">
        <div class="card kampius-card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="subheader">Outstanding Tagihan</div><div class="h2 mb-0">Rp {{ number_format($stats['outstanding'] ?? 0, 0, ',', '.') }}</div></div>
            <a href="{{ route('keuangan.verifikasi') }}" class="btn btn-sm btn-outline-primary">Verifikasi ({{ $stats['verifikasi_pending'] ?? 0 }})</a>
        </div></div>
    </div>
    @endif
    @if(auth()->user()->hasAnyRole(['dosen', 'superadmin', 'admin_prodi']))
    <div class="col-md-4">
        <div class="card kampius-card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="subheader">KRS Menunggu</div><div class="h2 mb-0">{{ $stats['krs_pending'] ?? 0 }} pengajuan</div></div>
            <a href="{{ route('dosen.krs.persetujuan') }}" class="btn btn-sm btn-outline-primary">Persetujuan</a>
        </div></div>
    </div>
    @endif
    @if(auth()->user()->hasAnyRole(['superadmin', 'admin_prodi']))
    <div class="col-md-4">
        <div class="card kampius-card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="subheader">PMB Menunggu</div><div class="h2 mb-0">{{ $stats['pmb_pending'] ?? 0 }} pendaftar</div></div>
            <a href="{{ route('admin.pmb.verifikasi') }}" class="btn btn-sm btn-outline-primary">Verifikasi PMB</a>
        </div></div>
    </div>
    @endif
    @if(auth()->user()->hasRole('mahasiswa'))
    <div class="col-md-4">
        <div class="card kampius-card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="subheader">Status KRS Saya</div><div class="h2 mb-0">{{ $saya['krs_status'] ?? '—' }}</div></div>
            <a href="{{ route('mahasiswa.krs.pengajuan') }}" class="btn btn-sm btn-outline-primary">Pengajuan KRS</a>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card kampius-card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="subheader">Tagihan Saya</div><div class="h2 mb-0">Rp {{ number_format($saya['outstanding'] ?? 0, 0, ',', '.') }}</div><div class="small kampius-muted">{{ $saya['tagihan_aktif'] ?? 0 }} tagihan aktif</div></div>
            <a href="{{ route('keuangan.bayar') }}" class="btn btn-sm btn-outline-primary">Bayar</a>
        </div></div>
    </div>
    @endif
    @if(auth()->user()->hasAnyRole(['pimpinan', 'superadmin']))
    <div class="col-md-4">
        <div class="card kampius-card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="subheader">Eksekutif</div><div class="h2 mb-0">Laporan</div></div>
            <a href="{{ route('dashboard.pimpinan') }}" class="btn btn-sm btn-outline-primary">Dashboard Pimpinan</a>
        </div></div>
    </div>
    @endif
</div>

{{-- CHARTS ROW --}}
<div class="row row-deck row-cards">
    <div class="col-lg-7">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Academic Performance <span class="kampius-muted fw-normal">• % KRS disetujui per semester</span></h3>
                <span class="badge bg-green-lt">Real data</span>
            </div>
            <div class="card-body">
                @if(!empty($perfValues) && array_sum($perfValues) > 0)
                    @php
                        $w = 100; $h = 32; $n = count($perfValues);
                        $pts = [];
                        foreach ($perfValues as $i => $v) {
                            $x = 4 + $i * (($w - 8) / max(1, $n - 1));
                            $y = $h - 4 - ($v / 100) * ($h - 10);
                            $pts[] = [round($x,1), round($y,1), $v];
                        }
                        $line = implode(' ', array_map(fn($p) => $p[0].','.$p[1], $pts));
                        $peak = collect($pts)->sortByDesc(fn($p) => $p[2])->first();
                    @endphp
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" style="height:200px" role="img" aria-label="Grafik performa akademik">
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
                        @if($peak)
                            <line x1="{{ $peak[0] }}" y1="{{ $peak[1] }}" x2="{{ $peak[0] }}" y2="{{ $h - 4 }}" stroke="#16A36A" stroke-width="0.4" stroke-dasharray="1,1"/>
                        @endif
                    </svg>
                    <div class="d-flex justify-content-between small kampius-muted mt-1 flex-wrap gap-1">
                        @foreach($perfLabels as $i => $lb)
                            <span class="text-center">{{ $lb }}<br><strong class="text-dark">{{ $perfValues[$i] }}%</strong></span>
                        @endforeach
                    </div>
                @else
                    <div class="empty py-4">
                        <p class="empty-title">Belum ada data performa</p>
                        <p class="empty-subtitle">Data muncul setelah ada KRS pada tahun akademik.</p>
                        <div class="empty-action"><a href="{{ route('admin.pmb.verifikasi') }}" class="btn btn-primary">Kelola PMB</a></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Earnings</h3>
                <div class="d-flex gap-3 small"><span><span class="badge bg-blue" style="width:10px;height:10px;padding:0">&nbsp;</span> Pemasukan</span><span><span class="badge bg-green" style="width:10px;height:10px;padding:0">&nbsp;</span> Tagihan terbit</span></div>
            </div>
            <div class="card-body">
                @php $maksBar = max(1, max($chartNominal ?? [0]), max($chartTagihan ?? [0])); @endphp
                @if(array_sum($chartNominal ?? []) + array_sum($chartTagihan ?? []) > 0)
                    <div class="d-flex align-items-end justify-content-between gap-2" style="height:200px">
                        @foreach($chartLabels as $i => $lb)
                            @php
                                $v1 = ($chartNominal[$i] ?? 0) / $maksBar * 100;
                                $v2 = ($chartTagihan[$i] ?? 0) / $maksBar * 100;
                            @endphp
                            <div class="flex-fill text-center">
                                <div class="d-flex justify-content-center gap-1 align-items-end" style="height:160px">
                                    <div title="Tagihan Rp {{ number_format($chartTagihan[$i] ?? 0, 0, ',', '.') }}" style="width:12px;height:{{ max(3,$v2) }}%;background:#16A36A;border-radius:4px"></div>
                                    <div title="Masuk Rp {{ number_format($chartNominal[$i] ?? 0, 0, ',', '.') }}" style="width:12px;height:{{ max(3,$v1) }}%;background:#1769F5;border-radius:4px"></div>
                                </div>
                                <div class="small kampius-muted mt-1">{{ $lb }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty py-4">
                        <p class="empty-title">Belum ada transaksi</p>
                        <p class="empty-subtitle">Pemasukan terverifikasi akan tampil di sini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- BOTTOM 3 COLUMNS ala referensi --}}
<div class="row row-deck row-cards mt-3">
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header d-flex justify-content-between"><h3 class="card-title">Messages</h3><a href="{{ route('publik.pengumuman') }}" class="small">View All</a></div>
            <div class="list-group list-group-flush">
                @forelse($messages as $m)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between"><strong>{{ $m->judul }}</strong><span class="small kampius-muted">{{ $m->published_at?->format('h:i A') }}</span></div>
                        <div class="small kampius-muted text-truncate" style="max-width:100%">{{ strip_tags($m->isi) }}</div>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">Belum ada pengumuman. Tambahkan pengumuman agar muncul di sini.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Students</h3></div>
            <div class="card-body text-center">
                @if(($stats['mahasiswa'] ?? 0) > 0)
                    @php
                        $colors = ['#1769F5','#11C5E8','#16A36A','#8AA2FF'];
                        $total = max(1, $stats['mahasiswa']);
                        $offset = 25;
                        $segments = [];
                        $i = 0;
                        foreach(($angkatanBreakdown ?? []) as $angk => $tot) {
                            $pct = $tot / $total * 100;
                            $segments[] = ['angk' => $angk, 'tot' => $tot, 'pct' => $pct, 'color' => $colors[$i % count($colors)], 'offset' => $offset];
                            $offset += $pct;
                            $i++;
                        }
                    @endphp
                    <svg viewBox="0 0 42 42" style="width:170px;height:170px" role="img" aria-label="Donut mahasiswa">
                        <circle cx="21" cy="21" r="15.9" fill="none" stroke="#EEF1F6" stroke-width="5"/>
                        @foreach($segments as $s)
                            <circle cx="21" cy="21" r="15.9" fill="none" stroke="{{ $s['color'] }}" stroke-width="5"
                                stroke-dasharray="{{ round($s['pct'],1) }} {{ round(100 - $s['pct'],1) }}" stroke-dashoffset="{{ round($s['offset'],1) }}" stroke-linecap="round"/>
                        @endforeach
                        <text x="21" y="20" text-anchor="middle" font-size="6" font-weight="bold" fill="#10234B">{{ number_format($stats['mahasiswa']) }}</text>
                        <text x="21" y="24.5" text-anchor="middle" font-size="2.6" fill="#718096">Total Students</text>
                    </svg>
                    <div class="mt-3 text-start">
                        @foreach($segments as $s)
                            <div class="d-flex justify-content-between small mb-1">
                                <span><span class="dot d-inline-block me-1" style="width:10px;height:10px;border-radius:3px;background:{{ $s['color'] }}"></span> {{ $s['angk'] }} ({{ number_format($s['pct'],2) }}%)</span>
                                <strong>{{ number_format($s['tot']) }} students</strong>
                            </div>
                        @endforeach
                        @if(!empty($statusBreakdown))
                            <hr><div class="small kampius-muted">Status: {{ collect($statusBreakdown)->map(fn($v,$k) => $k.' '.$v)->join(' • ') }}</div>
                        @endif
                    </div>
                @else
                    <div class="empty"><p class="empty-title">Belum ada mahasiswa</p><p class="empty-subtitle">Import data mahasiswa untuk melihat distribusi.</p></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Student Activity</h3></div>
            <div class="list-group list-group-flush">
                @forelse($aktivitasMahasiswa as $a)
                    <div class="list-group-item d-flex gap-2">
                        <span class="avatar avatar-sm bg-blue-lt">K</span>
                        <div><strong>{{ $a['judul'] }}</strong><div class="small kampius-muted">{{ $a['sub'] }}</div></div>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">Belum ada aktivitas KRS. Aktivitas terbaru akan tampil di sini.</div>
                @endforelse
                @forelse($agenda as $ag)
                    <div class="list-group-item d-flex gap-2">
                        <span class="badge bg-blue-lt">{{ $ag['tanggal'] }}</span>
                        <div><strong>{{ $ag['judul'] }}</strong><div class="small kampius-muted">{{ $ag['sub'] }}</div></div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Aktivitas + aksi cepat --}}
<div class="row row-cards mt-3">
    <div class="col-lg-8">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Aktivitas Terakhir</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($aktivitas as $a)
                            <tr><td>{{ $a->created_at?->format('d M H:i') }}</td><td>{{ $a->user->name ?? 'sistem' }}</td><td>{{ $a->action }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">Belum ada aktivitas tercatat.</td></tr>
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
                    @if(auth()->user()->hasAnyRole(['dosen','superadmin','admin_prodi']))
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
                <h4>Status KRS</h4>
                @forelse($krsStatus as $status => $total)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-{{ $status === 'disetujui' ? 'success' : ($status === 'diajukan' ? 'warning' : 'secondary') }}">{{ $status }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p class="text-secondary">Belum ada KRS.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
