@extends('layouts.tabler')

@section('title', 'PDDikti / Feeder')
@section('page-title', 'PDDikti / Feeder')

@section('content')
<div class="row row-cards">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h3 class="card-title">Mahasiswa</h3>
            <a href="{{ route('pddikti.export', 'mahasiswa') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h3 class="card-title">Nilai</h3>
            <a href="{{ route('pddikti.export', 'nilai') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h3 class="card-title">Kelulusan</h3>
            <a href="{{ route('pddikti.export', 'kelulusan') }}" class="btn btn-outline-primary">Download CSV</a>
        </div></div>
    </div>
</div>
<div class="alert alert-info mt-3">Push langsung ke Feeder via kredensial di halaman Integrasi. Format CSV delimiter <code>;</code> siap mapping ke Neo Feeder.</div>
@endsection
