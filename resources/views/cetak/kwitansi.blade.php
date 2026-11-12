@extends('layouts.tabler')

@section('title', 'Kwitansi #' . $pembayaran->id)
@section('page-title', 'Kwitansi Pembayaran')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Kwitansi #{{ $pembayaran->id }}</h3></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-4">NIM</dt><dd class="col-8">{{ $pembayaran->tagihan->mahasiswa->nim ?? '-' }}</dd>
            <dt class="col-4">Jenis</dt><dd class="col-8">{{ $pembayaran->tagihan->jenis }}</dd>
            <dt class="col-4">Tahun</dt><dd class="col-8">{{ $pembayaran->tagihan->tahunAkademik->tahun ?? '-' }} {{ $pembayaran->tagihan->tahunAkademik->semester ?? '' }}</dd>
            <dt class="col-4">Nominal bayar</dt><dd class="col-8">Rp {{ number_format($pembayaran->nominal, 0, ',', '.') }}</dd>
            <dt class="col-4">Total tagihan</dt><dd class="col-8">Rp {{ number_format($pembayaran->tagihan->nominal, 0, ',', '.') }}</dd>
            <dt class="col-4">Terbayar</dt><dd class="col-8">Rp {{ number_format($pembayaran->tagihan->terbayar, 0, ',', '.') }}</dd>
            <dt class="col-4">Status tagihan</dt><dd class="col-8"><span class="badge bg-{{ $pembayaran->tagihan->status === 'lunas' ? 'success' : 'warning' }}">{{ $pembayaran->tagihan->status }}</span></dd>
            <dt class="col-4">Metode</dt><dd class="col-8">{{ $pembayaran->metode }}</dd>
            <dt class="col-4">Terverifikasi</dt><dd class="col-8">{{ $pembayaran->verified_at ?? '-' }}</dd>
        </dl>
    </div>
</div>
@endsection
