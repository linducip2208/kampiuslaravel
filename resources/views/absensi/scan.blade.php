@extends('layouts.tabler')

@section('title', 'Absensi QR')
@section('pretitle', 'Absensi')
@section('page-title', 'Absensi QR')

@section('content')
@if($mahasiswaId)
    <div class="card">
        <div class="card-body">
            @livewire('absensi.scan-qr', ['mahasiswaId' => $mahasiswaId])
        </div>
    </div>
@else
    <div class="alert alert-info">Belum ada data mahasiswa.</div>
@endif
@endsection
