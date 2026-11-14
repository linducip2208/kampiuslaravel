@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', 'Akses ditolak')

@section('content')
<div class="kampius-empty mx-auto" style="max-width: 560px">
    <div class="kampius-empty-icon">◫</div>
    <h3>Akses ditolak (403)</h3>
    <p class="kampius-muted">Akun Anda tidak memiliki izin untuk halaman ini. Hubungi admin bila ini keliru.</p>
    <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Beranda</a>
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary ms-2">Buka Dashboard</a>
    @endauth
</div>
@endsection
