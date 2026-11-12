@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Dokumentasi')
@section('pretitle', 'Bantuan')
@section('page-title', 'Dokumentasi')

@section('content')
<div class="row">
    <div class="col-lg-3">
        <div class="card mb-3">
            <div class="list-group list-group-flush">
                @foreach($dokumen as $file => $judul)
                    <a href="{{ route('dokumen.show', $file) }}" class="list-group-item list-group-item-action {{ $aktif === $file ? 'active' : '' }}">{{ $judul }}</a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                @if($konten)
                    <div class="markdown">{!! $konten !!}</div>
                @else
                    <p class="text-secondary">Pilih dokumen di samping untuk dibaca. Dokumentasi diambil langsung dari repositori proyek.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
