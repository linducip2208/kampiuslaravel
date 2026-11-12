@extends('layouts.tabler')

@section('title', 'KHS Mahasiswa #' . $mahasiswaId)
@section('page-title', 'Kartu Hasil Studi')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Mahasiswa #{{ $mahasiswaId }}</h3>
        <div class="card-actions">
            <span class="badge bg-primary">IP Semester: {{ number_format($ip, 2) }}</span>
            <span class="badge bg-success">IPK: {{ number_format($ipk, 2) }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kelas</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Hadir</th><th>NA</th><th>Huruf</th><th>Bobot</th></tr></thead>
            <tbody>
                @foreach($nilai as $row)
                    <tr>
                        <td>{{ $row->kelas_id }}</td><td>{{ $row->tugas }}</td><td>{{ $row->uts }}</td>
                        <td>{{ $row->uas }}</td><td>{{ $row->kehadiran }}</td><td>{{ $row->na }}</td>
                        <td><span class="badge bg-secondary">{{ $row->huruf }}</span></td><td>{{ $row->bobot }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
