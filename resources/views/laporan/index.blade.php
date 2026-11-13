@extends('layouts.tabler')

@section('title', 'Laporan & Ekspor')
@section('pretitle', 'Laporan')
@section('page-title', 'Laporan & Ekspor')

@section('content')
<div class="row row-cards">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="card-title">Rekap Keuangan</h3>
            <p class="text-secondary">Unduh rekap tagihan (NIM, jenis, nominal, terbayar, diskon, status).</p>
            <a href="{{ route('laporan.export.tagihan') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="card-title">Rekap Absensi</h3>
            <p class="text-secondary">Unduh rekap kehadiran per pertemuan.</p>
            <a href="{{ route('laporan.export.absensi') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="card-title">Tunggakan</h3>
            <p class="text-secondary">Unduh tagihan belum lunas (nim, jenis, nominal, terbayar, sisa, status).</p>
            <a href="{{ route('laporan.export.tunggakan') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="card-title">PMB</h3>
            <p class="text-secondary">Unduh pendaftar PMB (no. daftar, nama, pilihan prodi, status).</p>
            <a href="{{ route('laporan.export.pmb') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="card-title">KRS</h3>
            <p class="text-secondary">Unduh KRS per mahasiswa per tahun akademik.</p>
            <a href="{{ route('laporan.export.krs') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h3 class="card-title">Nilai</h3>
            <p class="text-secondary">Unduh nilai per mahasiswa per kelas.</p>
            <a href="{{ route('laporan.export.nilai') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
</div>
@endsection
