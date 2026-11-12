@extends('layouts.tabler')

@section('title', 'Dashboard - '.config('app.name', 'SIMAK Kuliah'))
@section('pretitle', 'Ringkasan')
@section('page-title', 'Dashboard')

@section('content')
<div class="row row-deck row-cards">
    @php
        $cards = [
            ['label' => 'Mahasiswa', 'value' => $stats['mahasiswa'], 'color' => 'primary', 'route' => 'mahasiswa.krs.pengajuan'],
            ['label' => 'Dosen', 'value' => $stats['dosen'], 'color' => 'info', 'route' => 'dosen.krs.persetujuan'],
            ['label' => 'Program Studi', 'value' => $stats['prodi'], 'color' => 'success', 'route' => 'publik.prodi'],
            ['label' => 'Matakuliah', 'value' => $stats['matakuliah'], 'color' => 'warning', 'route' => 'lms.materi'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ $card['label'] }}</div>
                    <div class="h1 mb-3 text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                    <div><a href="{{ route($card['route']) }}" class="btn btn-sm btn-outline-{{ $card['color'] }}">Buka</a></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards mt-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Penerimaan Pembayaran (6 bulan)</h3></div>
            <div class="card-body">
                @php
                    $maks = max(1, max($chartNominal));
                    $w = 100; $h = 30;
                    $titik = [];
                    foreach ($chartNominal as $i => $v) {
                        $x = 4 + $i * (($w - 8) / max(1, count($chartNominal) - 1));
                        $y = $h - 3 - ($v / $maks) * ($h - 8);
                        $titik[] = round($x, 1).','.round($y, 1);
                    }
                    $area = implode(' ', $titik);
                @endphp
                <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" style="height: 160px" role="img" aria-label="Grafik penerimaan">
                    <polygon points="4,{{ $h - 3 }} {{ $area }} {{ $w - 4 }},{{ $h - 3 }}" fill="rgba(32,107,196,.12)"></polygon>
                    <polyline points="{{ $area }}" fill="none" stroke="#206bc4" stroke-width="1.2"></polyline>
                    @foreach($titik as $t)
                        @php [$cx, $cy] = explode(',', $t); @endphp
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="1" fill="#206bc4"></circle>
                    @endforeach
                </svg>
                <div class="d-flex justify-content-between text-secondary small">
                    @foreach($chartLabels as $i => $label)
                        <span>{{ $label }}<br>Rp {{ number_format($chartNominal[$i] / 1000000, 1) }} jt</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Status KRS</h3></div>
            <div class="card-body">
                @forelse($krsStatus as $status => $total)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-{{ $status === 'disetujui' ? 'success' : ($status === 'diajukan' ? 'warning' : 'secondary') }}">{{ $status }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p class="text-secondary">Belum ada KRS.</p>
                @endforelse
                <hr>
                <h4>Agenda Terdekat</h4>
                @forelse($agenda as $a)
                    <div class="mb-2">
                        <span class="badge bg-blue-lt me-1">{{ $a['tanggal'] }}</span>
                        {{ $a['judul'] }} <span class="text-secondary">— {{ $a['sub'] }}</span>
                    </div>
                @empty
                    <p class="text-secondary">Belum ada agenda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Aktivitas Terakhir</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter">
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

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Aksi Cepat</h3></div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.pmb.verifikasi') }}" class="btn btn-outline-primary">Verifikasi PMB</a>
            <a href="{{ route('nilai.input') }}" class="btn btn-outline-primary">Input Nilai</a>
            <a href="{{ route('keuangan.verifikasi') }}" class="btn btn-outline-primary">Verifikasi Bayar</a>
            <a href="{{ route('skripsi.pengajuan') }}" class="btn btn-outline-primary">Skripsi</a>
            <a href="{{ route('notifikasi.broadcast') }}" class="btn btn-outline-primary">Broadcast</a>
            <a href="{{ route('media.generate') }}" class="btn btn-outline-primary">Generate Gambar</a>
        </div>
    </div>
</div>
@endsection
