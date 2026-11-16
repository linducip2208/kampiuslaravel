@extends('layouts.tabler')

@section('title', 'Verifikasi SK Mengajar')
@section('page-title', 'Verifikasi SK Mengajar')

@section('content')
<div class="card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-4">Nomor</dt><dd class="col-8">{{ $sk->nomor }}</dd>
            <dt class="col-4">Dosen</dt><dd class="col-8">{{ $sk->dosen->user->name ?? '-' }} ({{ $sk->dosen->nidn ?? '-' }})</dd>
            <dt class="col-4">Total SKS</dt><dd class="col-8">{{ $sk->total_sks }}</dd>
        </dl>
        <div class="alert alert-success">Dokumen VALID.</div>
    </div>
</div>
@endsection
