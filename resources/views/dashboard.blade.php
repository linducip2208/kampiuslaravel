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
