@extends('layouts.tabler')

@section('title', 'Transkrip Akademik')
@section('page-title', 'Transkrip Akademik Sementara')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak / Simpan PDF</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Mahasiswa #{{ $mahasiswaId }}</h3>
        <div class="card-actions"><span class="badge bg-success">IPK: {{ number_format($ipk, 2) }}</span></div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kelas</th><th>NA</th><th>Huruf</th><th>Bobot</th></tr></thead>
            <tbody>
                @foreach($nilai as $row)
                    <tr><td>{{ $row->kelas_id }}</td><td>{{ $row->na }}</td><td><span class="badge bg-secondary">{{ $row->huruf }}</span></td><td>{{ $row->bobot }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
