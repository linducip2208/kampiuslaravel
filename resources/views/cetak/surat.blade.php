@extends('layouts.tabler')

@section('title', 'Surat ' . $surat->nomor_surat)
@section('page-title', 'Surat ' . strtoupper(str_replace('_', ' ', $surat->jenis)))
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Nomor: {{ $surat->nomor_surat }}</h3></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-4">NIM</dt><dd class="col-8">{{ $surat->mahasiswa->nim ?? '-' }}</dd>
            <dt class="col-4">Keperluan</dt><dd class="col-8">{{ $surat->keperluan }}</dd>
            <dt class="col-4">Status</dt><dd class="col-8"><span class="badge bg-secondary">{{ $surat->status }}</span></dd>
            <dt class="col-4">Verifikasi</dt><dd class="col-8"><a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></dd>
        </dl>
    </div>
</div>
@endsection
