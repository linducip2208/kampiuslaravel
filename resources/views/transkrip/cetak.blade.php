@extends('layouts.tabler')

@section('title', 'Transkrip Akademik')
@section('page-title', 'Transkrip Akademik Sementara')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak / Simpan PDF</button>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="h3 mb-0">{{ config('app.name', 'Universitas') }}</div>
            <div class="text-secondary">TRANSKRIP AKADEMIK SEMENTARA</div>
        </div>
        <dl class="row">
            <dt class="col-4">Nama</dt><dd class="col-8">{{ $mahasiswa->user->name ?? '-' }}</dd>
            <dt class="col-4">NIM</dt><dd class="col-8">{{ $mahasiswa->nim ?? '-' }}</dd>
            <dt class="col-4">Program Studi</dt><dd class="col-8">{{ $mahasiswa->prodi->nama ?? '-' }}</dd>
            <dt class="col-4">Angkatan</dt><dd class="col-8">{{ $mahasiswa->angkatan ?? '-' }}</dd>
        </dl>
        <div class="mb-3">
            <span class="badge bg-success">IPK: {{ number_format($ipk, 2) }}</span>
            <span class="badge bg-info">Total SKS: {{ $totalSks ?? 0 }}</span>
            <span class="badge bg-primary">Predikat: {{ $predikat }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Matakuliah</th><th>SKS</th><th>NA</th><th>Huruf</th><th>Bobot</th></tr></thead>
            <tbody>
                @forelse($nilai as $row)
                    <tr>
                        <td>{{ $row->kelas->matakuliah->kode ?? '-' }}</td>
                        <td>{{ $row->kelas->matakuliah->nama ?? ('Kelas #'.$row->kelas_id) }}</td>
                        <td>{{ $row->kelas->matakuliah->sks ?? 0 }}</td>
                        <td>{{ $row->na }}</td>
                        <td><span class="badge bg-secondary">{{ $row->huruf }}</span></td><td>{{ $row->bobot }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-secondary">Belum ada nilai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body d-print-none">
        <form method="GET" class="row g-2 align-items-end mb-2">
            <div class="col-auto">
                <label class="form-label" for="filter-tahun">Periode</label>
                <select name="tahun" id="filter-tahun" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua periode</option>
                    @foreach(($tahunOptions ?? []) as $id => $label)
                        <option value="{{ $id }}" @selected(($tahunFilter ?? null) == $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(!empty($tahunFilter))
                <div class="col-auto"><a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a></div>
            @endif
        </form>
        {{ $nilai->links() }}
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="text-secondary">{{ $tte['status'] }}</div>
                <div><strong>{{ $tte['penandatangan'] }}</strong></div>
                <div class="text-secondary small">ID Dokumen: {{ $tte['kode'] }} &middot; Hash: {{ $tte['hash'] }} &middot; {{ $tte['waktu'] }}</div>
                <div class="text-secondary small">Verifikasi: <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></div>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                @if($qrSvg)
                    {!! $qrSvg !!}
                    <div class="text-secondary small">Pindai untuk verifikasi</div>
                @else
                    <div class="text-secondary small">{{ $verifyUrl }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
