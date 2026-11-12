@extends('layouts.tabler')

@section('title', 'Input Nilai')
@section('pretitle', 'Nilai')
@section('page-title', 'Input Nilai')

@section('content')
@if($kelasId)
    <div class="card">
        <div class="card-body">
            @livewire('nilai.input-nilai', ['kelasId' => $kelasId])
        </div>
    </div>
@else
    <div class="alert alert-info">Belum ada kelas. Buat data kelas dulu, atau buka dengan <code>?kelas=ID</code>.</div>
@endif
@endsection
