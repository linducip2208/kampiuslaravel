@extends('layouts.tabler')

@section('title', 'KTM ' . $mahasiswa->nim)
@section('page-title', 'Kartu Tanda Mahasiswa Digital')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak / Simpan PDF</button>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="mx-auto" style="max-width: 540px; border: 2px solid #1f3a5f; border-radius: 12px; overflow: hidden;">
            <div class="px-3 py-2 text-white" style="background: #1f3a5f;">
                <div class="fw-bold">{{ config('app.name', 'Universitas') }}</div>
                <div class="small opacity-75">KARTU TANDA MAHASISWA (KTM) DIGITAL</div>
            </div>
            <div class="row g-0 p-3 align-items-center">
                <div class="col-3 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold" style="width: 72px; height: 72px; font-size: 28px; background: #206bc4;">
                        {{ strtoupper(substr($mahasiswa->user->name ?? $mahasiswa->nim, 0, 1)) }}
                    </div>
                    <div class="mt-1"><span class="badge bg-{{ $mahasiswa->status === 'aktif' ? 'success' : 'secondary' }}">{{ $mahasiswa->status }}</span></div>
                </div>
                <div class="col-6">
                    <div class="fw-bold">{{ $mahasiswa->user->name ?? '-' }}</div>
                    <div class="small text-secondary">NIM: {{ $mahasiswa->nim }}</div>
                    <div class="small text-secondary">{{ $mahasiswa->prodi->nama ?? '-' }}</div>
                    <div class="small text-secondary">Angkatan {{ $mahasiswa->angkatan ?? '-' }}</div>
                </div>
                <div class="col-3 text-center">
                    @if($qrSvg)
                        {!! $qrSvg !!}
                    @endif
                    <div class="small text-secondary">Pindai NIM</div>
                </div>
            </div>
            <div class="px-3 py-2 small text-secondary" style="background: #f1f5f9;">
                {{ $tte['status'] }} &middot; {{ $tte['hash'] }} &middot; {{ $tte['waktu'] }}
                <br>Verifikasi: <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
