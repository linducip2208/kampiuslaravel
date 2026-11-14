@extends('layouts.tabler')

@section('title', $title ?? 'SIMAK Kuliah')
@section('pretitle', 'Modul')
@section('page-title', $title ?? 'SIMAK Kuliah')

@section('breadcrumb')
    <ol class="breadcrumb" aria-label="Breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Modul</li>
        <li class="breadcrumb-item active" aria-current="page">{{ $title ?? 'Modul' }}</li>
    </ol>
@endsection

@section('content')
<div class="card kampius-card">
    <div class="card-body">
        @livewire($component)
    </div>
</div>
@endsection
