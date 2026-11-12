@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Verifikasi Surat')
@section('page-title', 'Verifikasi Surat')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-status-top bg-{{ $surat->status === 'selesai' ? 'success' : 'warning' }}"></div>
            <div class="card-body text-center">
                <h3 class="card-title">{{ $surat->nomor_surat }}</h3>
                <p class="text-secondary">Jenis: {{ $surat->jenis }} &middot; Status: {{ $surat->status }}</p>
                <div class="display-6">{{ $surat->status === 'selesai' ? 'VALID' : 'BELUM BERLAKU' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
