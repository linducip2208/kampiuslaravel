@extends('layouts.tabler')

@section('title', 'Pengajuan KRS')
@section('pretitle', 'KRS')
@section('page-title', 'Pengajuan Kartu Rencana Studi')

@section('breadcrumb')
    <ol class="breadcrumb" aria-label="Breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Akademik</li>
        <li class="breadcrumb-item active" aria-current="page">Pengajuan KRS</li>
    </ol>
@endsection

@section('content')
<div class="card kampius-card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">Pilih Kelas & Ajukan</h3>
            <p class="kampius-muted small mb-0">Batas SKS mengikuti IPK semester lalu. Kelas penuh bisa masuk waiting list.</p>
        </div>
    </div>
    <div class="card-body">
        <livewire:krs.pengajuan />
    </div>
</div>
@endsection
