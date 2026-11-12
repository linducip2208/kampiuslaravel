@extends('layouts.tabler')

@section('title', 'Tugas Perkuliahan')
@section('pretitle', 'LMS')
@section('page-title', 'Tugas Perkuliahan')

@section('content')
@if($kelasId)
    <div class="card">
        <div class="card-body">
            @livewire('lms.tugas-crud', ['kelasId' => $kelasId])
        </div>
    </div>
@else
    <div class="alert alert-info">Belum ada kelas. Buat data kelas dulu, atau buka dengan <code>?kelas=ID</code>.</div>
@endif
@endsection
