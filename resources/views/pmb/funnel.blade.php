@extends('layouts.tabler')

@section('title', 'Funnel PMB')
@section('pretitle', 'Pimpinan')
@section('page-title', 'Funnel Marketing PMB')

@section('content')
<div class="row row-deck row-cards">
    @php
        $cards = [
            ['label' => 'Total Pendaftar', 'value' => $total, 'color' => 'primary'],
            ['label' => 'VA Terbit', 'value' => $vaTerbit, 'color' => 'info'],
            ['label' => 'VA Lunas', 'value' => $vaLunas.' ('.$konversiBayar.'%)', 'color' => 'success'],
            ['label' => 'Konversi Verifikasi', 'value' => $konversiVerifikasi.'%', 'color' => 'warning'],
            ['label' => 'Konversi Lulus', 'value' => $konversiLulus.'%', 'color' => 'success'],
            ['label' => 'Beasiswa PMB', 'value' => $beasiswaPakai, 'color' => 'secondary'],
            ['label' => 'Follow-up WA', 'value' => $followUp, 'color' => 'info'],
            ['label' => 'Menunggu Verifikasi', 'value' => $diajukan, 'color' => 'danger'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ $card['label'] }}</div>
                    <div class="h1 mb-0 text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Per Status</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Status</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($byStatus as $status => $jumlah)
                            <tr><td>{{ $status }}</td><td class="text-end">{{ $jumlah }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-secondary">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Per Gelombang</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Gelombang</th><th class="text-end">Pendaftar</th><th class="text-end">Lulus</th></tr></thead>
                    <tbody>
                        @forelse($byGelombang as $row)
                            <tr><td>{{ $row->nama }}</td><td class="text-end">{{ $row->total }}</td><td class="text-end">{{ $row->lulus }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top 10 Sekolah Asal</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Sekolah</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($bySekolah as $row)
                            <tr><td>{{ $row->sekolah_asal ?? '-' }}</td><td class="text-end">{{ $row->total }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-secondary">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top Referral Sekolah</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Kode Referral</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($byReferral as $row)
                            <tr><td>{{ $row->kode }}</td><td class="text-end">{{ $row->total }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-secondary">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
