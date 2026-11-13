@extends('layouts.tabler')

@section('title', 'Dashboard Pimpinan - KAMPIUS')
@section('pretitle', 'Laporan Eksekutif')
@section('page-title', 'Dashboard Pimpinan')

@push('styles')
<style>
    .kampius-stat { border: 1px solid #E8EEF7; border-radius: 16px; background: #fff; }
    .kampius-stat .value { color: #10234B; font-weight: 700; font-size: 1.9rem; line-height: 1.1; }
    .kampius-card { border: 1px solid #E8EEF7; border-radius: 16px; }
    @media (max-width: 768px) { .kampius-stat .value { font-size: 1.5rem; } }
</style>
@endpush

@section('content')
<div class="row row-deck row-cards">
    @php
        $cards = [
            ['label' => 'Mahasiswa', 'value' => $stats['mahasiswa'] ?? 0, 'color' => 'primary'],
            ['label' => 'Dosen', 'value' => $stats['dosen'] ?? 0, 'color' => 'info'],
            ['label' => 'Judul Skripsi', 'value' => $stats['skripsi_judul'] ?? 0, 'color' => 'warning'],
            ['label' => 'Sidang', 'value' => $stats['sidang'] ?? 0, 'color' => 'success'],
            ['label' => 'Tagihan', 'value' => $stats['tagihan'] ?? 0, 'color' => 'primary'],
            ['label' => 'Belum Bayar', 'value' => $stats['tagihan_belum_bayar'] ?? 0, 'color' => 'danger'],
            ['label' => 'Pembayaran', 'value' => $stats['pembayaran'] ?? 0, 'color' => 'info'],
            ['label' => 'Pembayaran Pending', 'value' => $stats['pembayaran_pending'] ?? 0, 'color' => 'warning'],
            ['label' => 'Surat', 'value' => $stats['surat'] ?? 0, 'color' => 'success'],
            ['label' => 'Pengumuman', 'value' => $stats['pengumuman'] ?? 0, 'color' => 'secondary'],
            ['label' => 'KRS Pending', 'value' => $stats['krs_pending'] ?? 0, 'color' => 'warning'],
            ['label' => 'PMB Menunggu', 'value' => $stats['pmb_menunggu'] ?? 0, 'color' => 'info'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-sm-6 col-lg-3">
            <div class="card kampius-stat">
                <div class="card-body">
                    <div class="subheader">{{ $card['label'] }}</div>
                    <div class="value mt-1 text-{{ $card['color'] }}">{{ number_format($card['value']) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards mt-3">
    <div class="col-lg-6">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Keuangan Ringkas (real)</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Pendapatan terverifikasi</span><strong>Rp {{ number_format($stats['pendapatan_verified'] ?? 0, 0, ',', '.') }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Piutang aktif</span><strong>Rp {{ number_format($stats['piutang'] ?? 0, 0, ',', '.') }}</strong></div>
                <div class="d-flex justify-content-between"><span>KRS disetujui hari ini</span><strong>{{ $stats['krs_disetujui_hari_ini'] ?? 0 }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card kampius-card h-100">
            <div class="card-header"><h3 class="card-title">Tren Pendapatan 6 Bulan</h3></div>
            <div class="card-body">
                @if(!empty($tren) && collect($tren)->sum('nominal') > 0)
                    @php $maks = max(1, collect($tren)->max('nominal')); @endphp
                    <div class="d-flex align-items-end gap-2" style="height:140px">
                        @foreach($tren as $t)
                            <div class="flex-fill text-center">
                                <div title="Rp {{ number_format($t['nominal'], 0, ',', '.') }}" style="height:{{ max(3, $t['nominal'] / $maks * 100) }}%;background:#1769F5;border-radius:4px"></div>
                                <div class="small text-secondary mt-1">{{ $t['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-secondary mb-0">Belum ada pendapatan terverifikasi.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-3 d-flex flex-wrap gap-2">
    <a href="{{ route('pmb.funnel') }}" class="btn btn-primary">Lihat Funnel Marketing PMB</a>
    <a href="{{ route('laporan.index') }}" class="btn btn-outline-primary">Rekap Laporan</a>
    <a href="{{ route('pddikti.index') }}" class="btn btn-outline-primary">PDDIKTI</a>
</div>
@endsection
