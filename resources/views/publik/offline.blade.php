@extends('layouts.tabler')

@section('title', 'Luring')
@section('page-title', 'Tidak Ada Koneksi')

@section('content')
<div class="card">
    <div class="card-body text-center py-5">
        <div class="h3">Anda sedang luring</div>
        <p class="text-secondary">Halaman ini tersedia offline. Data terbaru membutuhkan koneksi internet.</p>
        <a href="/dashboard" class="btn btn-primary">Coba Lagi</a>
    </div>
</div>
@endsection
