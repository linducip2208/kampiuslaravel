@extends('layouts.tabler')

@section('title', 'Kerjakan Kuis')
@section('pretitle', 'LMS')
@section('page-title', 'Kerjakan Kuis')

@section('content')
<div class="card">
    <div class="card-body">
        @livewire('lms.kuis-kerjakan', ['quizId' => $quizId])
    </div>
</div>
@endsection
