@extends('layouts.tabler')

@section('title', 'Dashboard Pimpinan')
@section('pretitle', 'Laporan')
@section('page-title', 'Dashboard Pimpinan')

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
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ $card['label'] }}</div>
                    <div class="h1 mb-0 text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">
    <a href="{{ route('pmb.funnel') }}" class="btn btn-primary">Lihat Funnel Marketing PMB</a>
</div>
@endsection
