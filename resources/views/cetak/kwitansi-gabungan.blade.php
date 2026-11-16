@extends('layouts.tabler')

@section('title', 'Kwitansi Gabungan')
@section('page-title', 'Kwitansi Gabungan')
@section('page-actions')
    <button class="btn btn-outline-primary d-print-none" onclick="window.print()">Cetak</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Kwitansi Gabungan — {{ $mahasiswa->nim ?? '-' }} ({{ $mahasiswa->user->name ?? '-' }})</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>#</th><th>Jenis</th><th>Tahun</th><th>Metode</th><th>Terverifikasi</th><th class="text-end">Nominal</th></tr></thead>
                <tbody>
                    @foreach($bayar as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->tagihan->jenis ?? '-' }}</td>
                            <td>{{ $b->tagihan->tahunAkademik->tahun ?? '-' }} {{ $b->tagihan->tahunAkademik->semester ?? '' }}</td>
                            <td>{{ $b->metode ?? '-' }}</td>
                            <td>{{ $b->verified_at ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($b->nominal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="5" class="text-end">Total {{ $bayar->count() }} pembayaran</th><th class="text-end">Rp {{ number_format($total, 0, ',', '.') }}</th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
