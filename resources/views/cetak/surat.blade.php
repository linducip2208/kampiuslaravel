@extends('layouts.tabler')

@section('title', 'Surat ' . $surat->nomor_surat)
@section('page-title', $template['judul'])
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak / Simpan PDF</button>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="h3 mb-0">{{ config('app.name', 'Universitas') }}</div>
            <div class="text-secondary">{{ strtoupper($template['judul']) }}</div>
            <div class="text-secondary">Nomor: {{ $surat->nomor_surat }}</div>
        </div>
        <p class="text-secondary">Perihal: {{ $template['perihal'] }}</p>
        <p>Kepada Yth.</p>
        @foreach($template['paragraf'] as $i => $paragraf)
            @if($i === 0)
                <p>{{ $paragraf }}</p>
                <dl class="row">
                    <dt class="col-4">Nama</dt><dd class="col-8">{{ $surat->mahasiswa->user->name ?? '-' }}</dd>
                    <dt class="col-4">NIM</dt><dd class="col-8">{{ $surat->mahasiswa->nim ?? '-' }}</dd>
                    <dt class="col-4">Program Studi</dt><dd class="col-8">{{ $surat->mahasiswa->prodi->nama ?? '-' }}</dd>
                    <dt class="col-4">Angkatan</dt><dd class="col-8">{{ $surat->mahasiswa->angkatan ?? '-' }}</dd>
                    <dt class="col-4">Keperluan</dt><dd class="col-8">{{ $surat->keperluan ?? '-' }}</dd>
                </dl>
            @else
                <p>{{ $paragraf }}</p>
            @endif
        @endforeach
        <div class="row align-items-center mt-4">
            <div class="col-md-7">
                <div class="text-secondary">{{ $tte['status'] }}</div>
                <div><strong>{{ $tte['penandatangan'] }}</strong></div>
                <div class="text-secondary small">ID Dokumen: {{ $tte['kode'] }} &middot; Hash: {{ $tte['hash'] }} &middot; {{ $tte['waktu'] }}</div>
                <div class="text-secondary small">Verifikasi: <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                @if($qrSvg)
                    {!! $qrSvg !!}
                    <div class="text-secondary small">Pindai untuk verifikasi</div>
                @else
                    <div class="text-secondary small">{{ $verifyUrl }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
