@extends('layouts.tabler')

@section('title', 'Materi Perkuliahan')
@section('pretitle', 'LMS')
@section('page-title', 'Materi Perkuliahan')

@section('content')
@if($pertemuanId)
    <div class="card">
        <div class="card-body">
            @livewire('lms.materi-crud', ['pertemuanId' => $pertemuanId])
        </div>
    </div>
@else
    <div class="alert alert-info">Belum ada pertemuan. Buat jadwal/pertemuan dulu, atau buka dengan <code>?pertemuan=ID</code>.</div>
@endif
@endsection
