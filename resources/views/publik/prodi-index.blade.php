@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Program Studi')
@section('pretitle', 'Publik')
@section('page-title', 'Program Studi')

@push('styles')
<meta name="description" content="Daftar program studi, kurikulum, dan pendaftaran mahasiswa baru (PMB) online.">
<link rel="canonical" href="{{ route('publik.prodi') }}">
@endpush

@section('content')
<div class="row row-cards">
    @forelse($prodi as $p)
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">{{ $p->nama }}</h3>
                    <p class="text-secondary">{{ $p->jenjang }} &middot; {{ $p->fakultas->nama ?? '-' }}</p>
                    <a href="{{ route('publik.prodi.detail', $p->slug) }}" class="btn btn-outline-primary">Lihat Detail</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Belum ada data prodi.</div></div>
    @endforelse
</div>
@endsection
