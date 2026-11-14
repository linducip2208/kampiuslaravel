@extends('layouts.tabler')

@section('title', 'Persetujuan KRS')
@section('pretitle', 'KRS')
@section('page-title', 'Persetujuan KRS Mahasiswa Bimbingan')

@section('breadcrumb')
    <ol class="breadcrumb" aria-label="Breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Akademik</li>
        <li class="breadcrumb-item active" aria-current="page">Persetujuan KRS</li>
    </ol>
@endsection

@section('content')
<div class="card kampius-card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">Antrean Persetujuan</h3>
            <p class="kampius-muted small mb-0">Hanya KRS mahasiswa bimbingan Anda yang tampil di sini.</p>
        </div>
    </div>
    <div class="card-body">
        <livewire:krs.persetujuan-pa />
    </div>
</div>
@endsection
