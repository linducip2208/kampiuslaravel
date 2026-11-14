@extends('layouts.tabler')

@section('title', 'Kuis Perkuliahan')
@section('pretitle', 'LMS')
@section('page-title', 'Kuis Perkuliahan')

@section('content')
@if($kelasId)
    <div class="card">
        <div class="card-body">
            @livewire('lms.kuis-kelola', ['kelasId' => $kelasId])
        </div>
    </div>
@else
    <div class="alert alert-info">Belum ada kelas. Buka dengan <code>?kelas=ID</code>.</div>
@endif
@endsection
