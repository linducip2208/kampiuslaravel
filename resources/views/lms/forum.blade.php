@extends('layouts.tabler')

@section('title', 'Forum Diskusi')
@section('page-title', 'Forum Diskusi Kelas')

@section('content')
<div class="card">
    <div class="card-body">
        @livewire('lms.forum', ['kelasId' => $kelasId])
    </div>
</div>
@endsection
