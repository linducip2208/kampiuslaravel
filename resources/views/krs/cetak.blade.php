@extends('layouts.tabler')

@section('title', 'KRS #' . $krs->id)
@section('page-title', 'Kartu Rencana Studi')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m-2-2h16M7 13h10M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 17v4m10-4v4" /></svg>
        Cetak
    </button>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">KRS #{{ $krs->id }} — <span class="badge bg-{{ $krs->status === 'disetujui' ? 'success' : 'secondary' }}">{{ $krs->status }}</span></h3>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-4">Mahasiswa ID</dt><dd class="col-8">{{ $krs->mahasiswa_id }}</dd>
            <dt class="col-4">Tahun Akademik ID</dt><dd class="col-8">{{ $krs->tahun_akademik_id }}</dd>
            <dt class="col-4">Total SKS</dt><dd class="col-8">{{ $krs->total_sks }}</dd>
        </dl>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>No</th><th>Kelas ID</th><th>SKS</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($krs->details as $i => $detail)
                        <tr><td>{{ $i + 1 }}</td><td>{{ $detail->kelas_id }}</td><td>{{ $detail->sks }}</td><td>{{ $detail->status }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
