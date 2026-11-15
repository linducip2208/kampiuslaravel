@extends('layouts.tabler')

@section('title', 'Impor Bank Soal')
@section('pretitle', 'LMS')
@section('page-title', 'Impor Bank Soal (CSV)')

@section('content')
<div class="card">
    <div class="card-body">
        @livewire('lms.bank-soal-impor', ['matakuliahId' => $mkId])
    </div>
</div>
@endsection
