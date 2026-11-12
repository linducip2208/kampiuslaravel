@extends('layouts.tabler')

@section('title', $title ?? 'SIMAK Kuliah')
@section('pretitle', 'Modul')
@section('page-title', $title ?? 'SIMAK Kuliah')

@section('content')
<div class="card">
    <div class="card-body">
        @livewire($component)
    </div>
</div>
@endsection
