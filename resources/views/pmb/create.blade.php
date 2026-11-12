@extends('layouts.tabler')

@section('public-nav', true)
@section('title', 'Pendaftaran Mahasiswa Baru')
@section('page-title', 'Formulir Pendaftaran Mahasiswa Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Data Pendaftar</h3>
            </div>
            <div class="card-body">
                <livewire:pmb.daftar-publik />
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('pmb.cek') }}" class="btn btn-outline-primary">Cek pengumuman kelulusan</a>
            </div>
        </div>
    </div>
</div>
@endsection
