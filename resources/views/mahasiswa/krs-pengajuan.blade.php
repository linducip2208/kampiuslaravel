@extends('layouts.tabler')

@section('title', 'Pengajuan KRS')
@section('pretitle', 'KRS')
@section('page-title', 'Pengajuan Kartu Rencana Studi')

@section('content')
<div class="card">
    <div class="card-body">
        <livewire:krs.pengajuan />
    </div>
</div>
@endsection
