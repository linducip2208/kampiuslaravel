@extends('layouts.tabler')

@section('title', 'Perwalian PA')
@section('pretitle', 'Dosen')
@section('page-title', 'Dashboard Perwalian PA')

@section('content')
<div class="card">
    <div class="card-body">
        <livewire:dosen.wali-list />
    </div>
</div>
@endsection
