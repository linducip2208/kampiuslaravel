@extends('layouts.tabler')

@section('title', 'Bayar Tagihan')
@section('page-title', 'Bayar Tagihan')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Jenis</th><th>Nominal</th><th>Terbayar</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($tagihan as $t)
                    <tr>
                        <td>{{ $t->jenis }} {{ $t->tahunAkademik->tahun ?? '' }}</td>
                        <td>Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($t->terbayar, 0, ',', '.') }}</td>
                        <td>{{ $t->status }}</td>
                        <td>
                            @if($t->status !== 'lunas')
                                <button class="btn btn-sm btn-primary" onclick="bayar({{ $t->id }})">Buat VA</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary">Tidak ada tagihan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div id="va-result" class="mt-3"></div>

@if(isset($pending) && $pending->count())
<h3 class="mt-3 mb-2">VA Aktif (belum lunas)</h3>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Reference</th><th>VA</th><th>Nominal</th><th></th></tr></thead>
            <tbody>
                @foreach($pending as $p)
                    <tr id="row-{{ $p->id }}">
                        <td>{{ $p->reference }}</td>
                        <td><b>{{ $p->va_number ?? '-' }}</b></td>
                        <td>Rp {{ number_format($p->nominal, 0, ',', '.') }}</td>
                        <td><button class="btn btn-sm btn-outline-primary" onclick="cek({{ $p->id }})">Cek Status</button></td>
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
async function bayar(id) {
    const res = await fetch(`/keuangan/bayar/${id}/va`, {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}});
    const data = await res.json();
    document.getElementById('va-result').innerHTML = res.ok
        ? `<div class="alert alert-success">VA: <b>${data.va_number ?? '-'}</b> — Ref: ${data.reference} — Rp ${Number(data.nominal).toLocaleString('id-ID')}</div>`
        : `<div class="alert alert-warning">${data.message ?? 'Gagal membuat VA.'}</div>`;
    if (res.ok) setTimeout(() => location.reload(), 1500);
}
async function cek(id) {
    const res = await fetch(`/keuangan/bayar/status/${id}`, {headers: {'Accept': 'application/json'}});
    const data = await res.json();
    alert('Status: ' + data.status);
    if (data.status === 'verified') location.reload();
}
</script>
@endpush
