@extends('layouts.tabler')

@section('title', 'KHS ' . ($mahasiswa->nim ?? $mahasiswaId))
@section('page-title', 'Kartu Hasil Studi')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak / Simpan PDF</button>
@endsection

@section('content')
<div id="khs-offline-banner" class="alert alert-warning d-none d-print-none" role="alert">
    Luring — menampilkan ringkasan KHS tersimpan <span id="khs-offline-saat"></span>.
    <span id="khs-offline-ringkas"></span>
</div>
<div class="card">
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="h3 mb-0">{{ config('app.name', 'Universitas') }}</div>
            <div class="text-secondary">KARTU HASIL STUDI (KHS)</div>
        </div>
        <dl class="row">
            <dt class="col-4">Nama</dt><dd class="col-8">{{ $mahasiswa->user->name ?? '-' }}</dd>
            <dt class="col-4">NIM</dt><dd class="col-8">{{ $mahasiswa->nim ?? '-' }}</dd>
            <dt class="col-4">Program Studi</dt><dd class="col-8">{{ $mahasiswa->prodi->nama ?? '-' }}</dd>
            <dt class="col-4">Angkatan</dt><dd class="col-8">{{ $mahasiswa->angkatan ?? '-' }}</dd>
        </dl>
        <div class="mb-3">
            <span class="badge bg-primary">IP Semester: {{ number_format($ip, 2) }}</span>
            <span class="badge bg-success">IPK: {{ number_format($ipk, 2) }}</span>
            <span class="badge bg-info">Total SKS: {{ $totalSks ?? 0 }}</span>
        </div>
    </div>
    @if(!empty($perTahun))
    <div class="card-body">
        <div class="row row-cards">
            @foreach($perTahun as $tahunId => $aggr)
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm"><div class="card-body">
                        <div class="subheader">Tahun {{ $tahunId }}</div>
                        <div class="h3 mb-0">IP {{ number_format($aggr['ip'], 2) }} &middot; {{ $aggr['sks'] }} SKS</div>
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Matakuliah</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Hadir</th><th>NA</th><th>Huruf</th><th>Bobot</th></tr></thead>
            <tbody>
                @forelse($nilai as $row)
                    <tr>
                        <td>{{ $row->kelas->matakuliah->kode ?? '-' }}</td>
                        <td>{{ $row->kelas->matakuliah->nama ?? ('Kelas #'.$row->kelas_id) }}</td>
                        <td>{{ $row->tugas }}</td><td>{{ $row->uts }}</td>
                        <td>{{ $row->uas }}</td><td>{{ $row->kehadiran }}</td><td>{{ $row->na }}</td>
                        <td><span class="badge bg-secondary">{{ $row->huruf }}</span></td><td>{{ $row->bobot }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-secondary">Belum ada nilai.</td></tr>
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
@push('scripts')
<script>
// KHS offline: simpan ringkasan ke localStorage tiap kunjungan; tampilkan
// banner + ringkasan saat luring (SW menyajikan cache halaman).
(function () {
    try {
        var kunci = 'khs-cache-{{ $mahasiswaId }}';
        var data = {ipk: {{ number_format($ipk, 2, '.', '') }}, sks: {{ (int) ($totalSks ?? 0) }}, saat: new Date().toISOString()};
        localStorage.setItem(kunci, JSON.stringify(data));
        if (!navigator.onLine) {
            var b = document.getElementById('khs-offline-banner');
            var c = JSON.parse(localStorage.getItem(kunci) || 'null');
            if (b && c) {
                document.getElementById('khs-offline-saat').textContent = '(' + c.saat + ')';
                document.getElementById('khs-offline-ringkas').textContent = 'IPK ' + c.ipk + ' · ' + c.sks + ' SKS.';
                b.classList.remove('d-none');
            }
        }
        window.addEventListener('offline', function () {
            document.getElementById('khs-offline-banner').classList.remove('d-none');
        });
    } catch (e) { /* penyimpanan lokal tak tersedia: abaikan */ }
})();
</script>
@endpush
@endsection
