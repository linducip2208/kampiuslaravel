@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Pengumuman')
@section('pretitle', 'Publik')
@section('page-title', 'Pengumuman & Berita')

@section('content')
<div class="row row-cards">
    @forelse($pengumuman as $p)
        <div class="col-md-6 col-lg-4">
            <div class="card">
                @if($p->cover_path)
                    <img src="{{ asset('storage/' . $p->cover_path) }}" class="card-img-top" alt="{{ $p->judul }}">
                @endif
                <div class="card-body">
                    <div class="text-secondary mb-1">{{ $p->published_at?->format('d M Y') }} &middot; {{ $p->prodi->nama ?? 'Semua' }}</div>
                    <h3 class="card-title">{{ $p->judul }}</h3>
                    <p class="text-secondary">{{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 120) }}</p>
                    <a href="{{ route('publik.pengumuman.detail', $p->id) }}" class="btn btn-outline-primary">Baca</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Belum ada pengumuman.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $pengumuman->links() }}</div>
@endsection
