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
                            <dt class="col-4">Pilihan Prodi</dt><dd class="col-8">{{ $pendaftar->prodi1?->nama ?? '-' }}@if($pendaftar->prodi2) / {{ $pendaftar->prodi2?->nama }}@endif</dd>
                            <dt class="col-4">Status</dt>
                            <dd class="col-8"><span class="badge bg-{{ $pendaftar->status === 'lulus' ? 'success' : ($pendaftar->status === 'tidak' ? 'danger' : 'secondary') }}">{{ $pendaftar->status }}</span></dd>
                            <dt class="col-4">VA Pendaftaran</dt><dd class="col-8">{{ $pendaftar->va_number ?? '-' }} ({{ $pendaftar->bayar_status ?? 'belum_bayar' }})</dd>
                            @if($pendaftar->referral_kode)
                                <dt class="col-4">Referral</dt><dd class="col-8">{{ $pendaftar->referral_kode }}</dd>
                            @endif
                            @if(in_array($pendaftar->status, ['lulus', 'tidak'], true))
                                <dt class="col-4">Nilai CBT</dt><dd class="col-8">{{ $pendaftar->nilaiJenis('cbt') ?? '-' }}</dd>
                                <dt class="col-4">Nilai Wawancara</dt><dd class="col-8">{{ $pendaftar->nilaiJenis('wawancara') ?? '-' }}</dd>
                                <dt class="col-4">Rata-rata</dt><dd class="col-8">{{ $pendaftar->nilaiAkhir() ?? '-' }}</dd>
                            @endif
                            @if($pendaftar->catatan_verifikasi)
                                <dt class="col-4">Catatan</dt><dd class="col-8">{{ $pendaftar->catatan_verifikasi }}</dd>
                            @endif
                        </dl>
                        @if($pendaftar->status === 'lulus')
                            <div class="alert alert-success">Selamat! Anda dinyatakan <strong>LULUS</strong>. Tunjukkan nomor pendaftaran ini saat daftar ulang di kampus.</div>
                        @elseif($pendaftar->status === 'tidak')
                            <div class="alert alert-danger">Mohon maaf, Anda dinyatakan <strong>TIDAK LULUS</strong> pada gelombang ini.</div>
                        @else
                            <div class="alert alert-info">Berkas Anda sedang diproses. Keputusan kelulusan akan tampil di halaman ini.</div>
                        @endif
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
