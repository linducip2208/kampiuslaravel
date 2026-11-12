@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Cek Kelulusan PMB')
@section('page-title', 'Cek Pengumuman Kelulusan PMB')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('pmb.cek') }}">
                    <div class="mb-3">
                        <label class="form-label" for="no_daftar">Nomor Pendaftaran</label>
                        <input id="no_daftar" type="text" name="no_daftar" class="form-control" value="{{ request('no_daftar') }}" placeholder="PMB-2026-000001">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Cek</button>
                </form>
            </div>
        </div>
        @if(request()->filled('no_daftar'))
            @if($pendaftar)
                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title">Hasil untuk {{ $pendaftar->no_daftar }}</h3></div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-4">Nama</dt><dd class="col-8">{{ $pendaftar->nama }}</dd>
                            <dt class="col-4">Gelombang</dt><dd class="col-8">{{ $pendaftar->gelombang?->nama ?? '-' }}</dd>
                            <dt class="col-4">Status</dt>
                            <dd class="col-8"><span class="badge bg-{{ $pendaftar->status === 'lulus' ? 'success' : 'secondary' }}">{{ $pendaftar->status }}</span></dd>
                            @if($pendaftar->catatan_verifikasi)
                                <dt class="col-4">Catatan</dt><dd class="col-8">{{ $pendaftar->catatan_verifikasi }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            @else
                <div class="alert alert-warning mt-3">Nomor pendaftaran tidak ditemukan.</div>
            @endif
        @endif
        <div class="text-center mt-3">
            <a href="{{ route('pmb.create') }}">Kembali ke formulir pendaftaran</a>
        </div>
    </div>
</div>
@endsection
