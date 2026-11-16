@extends('layouts.tabler')

@section('title', 'SK Mengajar ' . $sk->nomor)
@section('page-title', 'SK Beban Mengajar')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak / Simpan PDF</button>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="h3 mb-0">{{ config('app.name', 'Universitas') }}</div>
            <div class="text-secondary">SURAT KEPUTUSAN BEBAN MENGAJAR</div>
            <div class="text-secondary">Nomor: {{ $sk->nomor }}</div>
        </div>
        <dl class="row">
            <dt class="col-4">Nama Dosen</dt><dd class="col-8">{{ $sk->dosen->user->name ?? '-' }}</dd>
            <dt class="col-4">NIDN</dt><dd class="col-8">{{ $sk->dosen->nidn ?? '-' }}</dd>
            <dt class="col-4">Tahun Akademik</dt><dd class="col-8">{{ $tahunAkademik->tahun ?? '-' }} {{ ucfirst((string) ($tahunAkademik->semester ?? '')) }}</dd>
            <dt class="col-4">Tanggal Terbit</dt><dd class="col-8">{{ $sk->tanggal_terbit?->format('d M Y') ?? '-' }}</dd>
            <dt class="col-4">Total SKS</dt><dd class="col-8">{{ $sk->total_sks }} SKS</dd>
            <dt class="col-4">Daftar Kelas</dt><dd class="col-8">{{ $sk->daftar_kelas ?? '-' }}</dd>
        </dl>
        <div class="d-flex gap-3 align-items-center mt-3">
            @if(!empty($qrSvg))<div>{!! $qrSvg !!}</div>@endif
            <div class="text-secondary small">Pindai untuk verifikasi: <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a><br>{{ $tte['kode'] ?? '' }} — {{ $tte['waktu'] ?? '' }}</div>
        </div>
    </div>
</div>
@endsection
