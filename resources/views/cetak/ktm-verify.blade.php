@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Verifikasi KTM')
@section('page-title', 'Verifikasi Kartu Tanda Mahasiswa')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-status-top bg-{{ $mahasiswa->status === 'aktif' ? 'success' : 'warning' }}"></div>
            <div class="card-body text-center">
                <div class="display-6">{{ $mahasiswa->status === 'aktif' ? 'VALID' : 'TIDAK AKTIF' }}</div>
                <h3 class="card-title mt-2">{{ $mahasiswa->user->name ?? '-' }}</h3>
                <p class="text-secondary">NIM: {{ $mahasiswa->nim }} &middot; {{ $mahasiswa->prodi->nama ?? '-' }} &middot; Angkatan {{ $mahasiswa->angkatan ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
