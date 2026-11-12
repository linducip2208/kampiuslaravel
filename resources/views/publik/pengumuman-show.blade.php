@extends('layouts.tabler')

@section('public-nav', true)
@section('title', $item->judul)
@section('pretitle', 'Pengumuman')
@section('page-title', $item->judul)

@section('content')
<div class="card">
    @if($item->cover_path)
        <img src="{{ asset('storage/' . $item->cover_path) }}" class="card-img-top" alt="{{ $item->judul }}">
    @endif
    <div class="card-body">
        <div class="text-secondary mb-2">{{ $item->published_at?->format('d M Y H:i') }} &middot; {{ $item->prodi->nama ?? 'Semua' }} &middot; {{ $item->target }}</div>
        <div>{!! nl2br(e($item->isi)) !!}</div>
    </div>
</div>
@endsection
