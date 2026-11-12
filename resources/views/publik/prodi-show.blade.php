@extends('layouts.tabler')

@section('public-nav', true)
@section('title', $prodi->nama)
@section('pretitle', 'Program Studi')
@section('page-title', $prodi->nama)

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <p class="text-secondary mb-1">{{ $prodi->jenjang }} &middot; {{ $prodi->fakultas->nama ?? '-' }} &middot; Kode: {{ $prodi->kode }}</p>
        <p>Program studi {{ $prodi->nama }} dengan {{ $prodi->matakuliah->count() }} matakuliah terdaftar di kurikulum aktif.</p>
        <a href="{{ route('pmb.create') }}" class="btn btn-primary">Daftar via PMB</a>
    </div>
</div>

<h3 class="mb-2">Matakuliah</h3>
<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Nama</th><th>SKS</th><th>Semester</th></tr></thead>
            <tbody>
                @forelse($prodi->matakuliah as $mk)
                    <tr><td>{{ $mk->kode }}</td><td>{{ $mk->nama }}</td><td>{{ $mk->sks }}</td><td>{{ $mk->semester }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-secondary">Belum ada matakuliah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<h3 class="mb-2">Berita Prodi</h3>
<div class="row row-cards">
    @forelse($berita as $b)
        <div class="col-md-6">
            <div class="card">
                @if($b->cover_path)
                    <img src="{{ asset('storage/' . $b->cover_path) }}" class="card-img-top" alt="{{ $b->judul }}">
                @endif
                <div class="card-body">
                    <h3 class="card-title">{{ $b->judul }}</h3>
                    <a href="{{ route('publik.pengumuman.detail', $b->id) }}">Baca selengkapnya</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Belum ada berita.</div></div>
    @endforelse
</div>
@endsection
