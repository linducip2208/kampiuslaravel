@extends('layouts.tabler')

@section('title', 'Pengumpulan Tugas')
@section('pretitle', 'LMS')
@section('page-title', 'Pengumpulan Tugas')

@section('content')
@if($tugasId && $mahasiswaId)
    <div class="card">
        <div class="card-body">
            @livewire('lms.kumpul-tugas', ['tugasId' => $tugasId, 'mahasiswaId' => $mahasiswaId])
        </div>
    </div>
@else
    <div class="alert alert-info">Belum ada tugas/mahasiswa. Buat data dulu, atau buka dengan <code>?tugas=ID</code>.</div>
@endif
@endsection
