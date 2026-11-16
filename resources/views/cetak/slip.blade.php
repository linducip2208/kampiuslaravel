@extends('layouts.tabler')

@section('title', 'Slip Gaji #' . $slip->id)
@section('page-title', 'Slip Gaji')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Slip Gaji — {{ $slip->pegawai->name ?? ('#'.$slip->user_id) }}</h3></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-4">Periode</dt><dd class="col-8">{{ $slip->periode->periode ?? '-' }}</dd>
            <dt class="col-4">Gaji pokok</dt><dd class="col-8">Rp {{ number_format($slip->gaji_pokok, 0, ',', '.') }}</dd>
            <dt class="col-4">Tunjangan</dt><dd class="col-8">Rp {{ number_format($slip->tunjangan, 0, ',', '.') }}</dd>
            <dt class="col-4">Potongan</dt><dd class="col-8">Rp {{ number_format($slip->potongan, 0, ',', '.') }}</dd>
            <dt class="col-4"><strong>Total bersih</strong></dt><dd class="col-8"><strong>Rp {{ number_format($slip->total_bersih, 0, ',', '.') }}</strong></dd>
            <dt class="col-4">Status</dt><dd class="col-8">{{ $slip->status }}</dd>
        </dl>
    </div>
</div>
@endsection
