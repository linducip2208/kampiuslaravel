@extends('layouts.tabler')

@section('title', 'Bayar Tagihan')
@section('pretitle', 'Keuangan')
@section('page-title', 'Bayar Tagihan')

@section('breadcrumb')
    <ol class="breadcrumb" aria-label="Breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bayar Tagihan</li>
    </ol>
@endsection

@section('content')
<div class="card kampius-card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('keuangan.bayar.manual') }}" class="btn btn-outline-primary">Upload Bukti Transfer Manual</a>
        <span class="kampius-muted small">VA/QRIS/e-wallet dibuat instan, manual diverifikasi petugas.</span>
    </div>
</div>
<div class="card kampius-card">
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead><tr><th>Jenis</th><th>Nominal</th><th>Terbayar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($tagihan as $t)
                    <tr>
                        <td><strong>{{ $t->jenis }}</strong> <span class="kampius-muted">{{ $t->tahunAkademik->tahun ?? '' }}</span></td>
                        <td>Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($t->terbayar, 0, ',', '.') }}</td>
                        <td><span class="badge bg-{{ $t->status === 'lunas' ? 'success' : ($t->status === 'sebagian' ? 'warning' : 'secondary') }}">{{ $t->status }}</span></td>
                        <td class="text-end">
                            @if($t->status !== 'lunas')
                                <div class="btn-list justify-content-end">
                                    <button class="btn btn-sm btn-primary" onclick="bayar({{ $t->id }}, 'va')">Buat VA</button>
                                    <button class="btn btn-sm btn-danger" onclick="bayar({{ $t->id }}, 'qris')">QRIS</button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="bayar({{ $t->id }}, 'gopay')">GoPay</button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="bayar({{ $t->id }}, 'ovo')">OVO</button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="bayar({{ $t->id }}, 'dana')">DANA</button>
                                </div>
                            @else
                                <span class="kampius-muted small">Lunas ✓</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Tidak ada tagihan</h3></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div id="va-result" class="mt-3" role="status" aria-live="polite"></div>

@if(isset($pending) && $pending->count())
<h3 class="mt-3 mb-2">VA Aktif (belum lunas)</h3>
<div class="card kampius-card">
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead><tr><th>Reference</th><th>VA</th><th>Nominal</th><th class="text-end"></th></tr></thead>
            <tbody>
                @foreach($pending as $p)
                    <tr id="row-{{ $p->id }}">
                        <td><code>{{ $p->reference }}</code></td>
                        <td><b>{{ $p->va_number ?? '-' }}</b></td>
                        <td>Rp {{ number_format($p->nominal, 0, ',', '.') }}</td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-primary" onclick="cek({{ $p->id }})">Cek Status</button> <span id="st-{{ $p->id }}" class="small kampius-muted ms-1"></span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
async function bayar(id, method = 'va') {
    const slot = document.getElementById('va-result');
    slot.innerHTML = '<div class="alert alert-info">Membuat kanal pembayaran…</div>';
    const body = new FormData();
    body.append('method', method);
    const res = await fetch(`/keuangan/bayar/${id}/va`, {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}, body});
    const data = await res.json();
    const kode = data.qr_payload ? `<br>QR/deeplink: <code>${data.qr_payload}</code>` : '';
    const va = data.va_number ? `<b>${data.va_number}</b> — ` : '';
    slot.innerHTML = res.ok
        ? `<div class="alert alert-success">${va}Ref: ${data.reference} — Rp ${Number(data.nominal).toLocaleString('id-ID')} (${data.metode ?? method})${kode}</div>`
        : `<div class="alert alert-warning">${data.message ?? 'Gagal membuat pembayaran.'}</div>`;
    if (res.ok) setTimeout(() => location.reload(), 2500);
}
async function cek(id) {
    const slot = document.getElementById('st-' + id);
    if (slot) slot.textContent = 'mengecek…';
    const res = await fetch(`/keuangan/bayar/status/${id}`, {headers: {'Accept': 'application/json'}});
    const data = await res.json();
    if (slot) slot.textContent = 'Status: ' + data.status;
    if (data.status === 'verified') location.reload();
}
</script>
@endpush
