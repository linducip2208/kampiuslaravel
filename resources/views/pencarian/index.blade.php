@extends('layouts.tabler')

@section('title', 'Pencarian: '.$q)
@section('pretitle', 'Pencarian katalog')
@section('page-title', 'Hasil pencarian')

@section('breadcrumb')
    <ol class="breadcrumb" aria-label="Breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Pencarian</li>
    </ol>
@endsection

@section('content')
<div class="kampius-search-summary">
    <p class="kampius-muted">Hasil untuk <strong class="text-dark">“{{ $q }}”</strong>
    @if(mb_strlen($q) < 2)
        — ketik minimal 2 karakter pada kolom pencarian di bilah atas.
    @endif
    </p>
</div>

@php
    $total = $hasil['pengumuman']->count() + $hasil['prodi']->count() + $hasil['matakuliah']->count();
@endphp

@if(mb_strlen($q) >= 2 && $total === 0)
    <div class="kampius-empty">
        <div class="kampius-empty-icon">◎</div>
        <h3>Tidak ada hasil</h3>
        <p class="kampius-muted">Coba kata kunci lain, misalnya nama program studi atau mata kuliah.</p>
    </div>
@endif

@if($hasil['pengumuman']->isNotEmpty())
<div class="card kampius-card mb-3">
    <div class="card-header"><h3 class="card-title">Pengumuman ({{ $hasil['pengumuman']->count() }})</h3></div>
    <div class="list-group list-group-flush">
        @foreach($hasil['pengumuman'] as $p)
            <a href="{{ route('publik.pengumuman.detail', $p->id) }}" class="list-group-item list-group-item-action">
                <strong>{{ $p->judul }}</strong>
                <span class="small kampius-muted d-block">{{ $p->published_at?->format('d M Y') }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif

@if($hasil['prodi']->isNotEmpty())
<div class="card kampius-card mb-3">
    <div class="card-header"><h3 class="card-title">Program Studi ({{ $hasil['prodi']->count() }})</h3></div>
    <div class="list-group list-group-flush">
        @foreach($hasil['prodi'] as $pr)
            <a href="{{ route('publik.prodi.detail', $pr->slug) }}" class="list-group-item list-group-item-action">
                <strong>{{ $pr->nama }}</strong>
                <span class="small kampius-muted d-block">{{ $pr->kode }} · {{ $pr->jenjang }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif

@if($hasil['matakuliah']->isNotEmpty())
<div class="card kampius-card mb-3">
    <div class="card-header"><h3 class="card-title">Mata Kuliah ({{ $hasil['matakuliah']->count() }})</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead><tr><th>Kode</th><th>Nama</th><th>SKS</th><th>Prodi</th></tr></thead>
            <tbody>
                @foreach($hasil['matakuliah'] as $mk)
                    <tr><td><span class="badge bg-blue-lt">{{ $mk->kode }}</span></td><td>{{ $mk->nama }}</td><td>{{ $mk->sks }}</td><td class="kampius-muted">{{ $mk->prodi->nama ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
