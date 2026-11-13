@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Magang Hub')
@section('pretitle', 'Publik')
@section('page-title', 'Lowongan Magang MBKM')

@section('content')
<div class="row row-cards">
    @forelse($lowongan as $l)
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary mb-1">{{ $l->mitra->nama ?? '-' }} &middot; Kuota {{ $l->kuota }}</div>
                    <h3 class="card-title">{{ $l->judul }}</h3>
                    <p class="text-secondary">{{ \Illuminate\Support\Str::limit($l->deskripsi ?? '', 120) }}</p>
                    <p class="text-secondary">Batas: {{ $l->deadline?->format('d M Y') ?? '-' }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Belum ada lowongan magang.</div></div>
    @endforelse
</div>
@endsection
