@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Verifikasi Transkrip')
@section('page-title', 'Verifikasi Transkrip Akademik')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-status-top bg-success"></div>
            <div class="card-body text-center">
                <div class="display-6">VALID</div>
                <h3 class="card-title mt-2">{{ $mahasiswa->user->name ?? '-' }} — {{ $mahasiswa->nim ?? '-' }}</h3>
                <p class="text-secondary">{{ $mahasiswa->prodi->nama ?? '-' }} &middot; Angkatan {{ $mahasiswa->angkatan ?? '-' }}</p>
                <dl class="row text-start">
                    <dt class="col-6">IPK</dt><dd class="col-6">{{ number_format($ipk, 2) }}</dd>
                    <dt class="col-6">Total SKS</dt><dd class="col-6">{{ $totalSks }}</dd>
                    <dt class="col-6">Predikat</dt><dd class="col-6">{{ $predikat }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
