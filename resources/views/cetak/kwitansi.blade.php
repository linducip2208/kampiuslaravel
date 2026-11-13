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
        <hr>
        <h4>Rincian Pajak</h4>
        <dl class="row">
            @if(!empty($pajak['npwp'] ?? ''))
                <dt class="col-4">NPWP Kampus</dt><dd class="col-8">{{ $pajak['npwp'] }}</dd>
            @endif
            <dt class="col-4">DPP</dt><dd class="col-8">Rp {{ number_format($pajak['dpp'] ?? $pembayaran->nominal, 0, ',', '.') }}</dd>
            <dt class="col-4">PPN ({{ rtrim(rtrim(number_format($pajak['tarif'] ?? 0, 2, ',', '.'), '0'), ',') }}%)</dt><dd class="col-8">Rp {{ number_format($pajak['ppn'] ?? 0, 0, ',', '.') }}</dd>
            <dt class="col-4"><strong>Total bayar (termasuk PPN)</strong></dt><dd class="col-8"><strong>Rp {{ number_format($pajak['bruto'] ?? $pembayaran->nominal, 0, ',', '.') }}</strong></dd>
        </dl>
        @if(($pajak['tarif'] ?? 0) == 0)
            <p class="text-secondary">Jasa pendidikan — PPN 0% sesuai ketentuan yang berlaku.</p>
        @endif
    </div>
</div>
@endsection
