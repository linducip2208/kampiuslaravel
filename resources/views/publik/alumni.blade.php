@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Portal Alumni')
@section('pretitle', 'Publik')
@section('page-title', 'Portal Alumni')

@section('content')
<div class="row row-cards">
    @forelse($alumni as $a)
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">{{ $a->mahasiswa->user->name ?? 'Alumni' }}</h3>
                    <p class="text-secondary">{{ $a->mahasiswa->prodi->nama ?? '-' }} &middot; Lulus {{ $a->tahun_lulus }}</p>
                    <p class="text-secondary">{{ $a->pekerjaan ?? '—' }} @if($a->instansi) &middot; {{ $a->instansi }} @endif</p>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Belum ada data alumni.</div></div>
    @endforelse
</div>
@endsection
