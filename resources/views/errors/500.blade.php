@extends('layouts.tabler')

@section('public-nav', true)
@section('no-header', true)
@section('title', 'Terjadi kesalahan')

@section('content')
<div class="kampius-empty mx-auto" style="max-width: 560px">
    <div class="kampius-empty-icon">◫</div>
    <h3>Terjadi kesalahan (500)</h3>
    <p class="kampius-muted">Tim kami telah mencatat kejadian ini. Silakan coba lagi beberapa saat.</p>
    <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Beranda</a>
</div>
@endsection
