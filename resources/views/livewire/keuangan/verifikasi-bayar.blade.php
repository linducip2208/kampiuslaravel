<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Rekonsiliasi Mutasi Bank (CSV)</h3></div>
        <div class="card-body">
            <p class="text-secondary">Format: <code>reference,nominal</code> per baris (header opsional). Nominal cocok persis → ditandai rekonsiliasi.</p>
            <form wire:submit="imporRekonsiliasi" class="d-flex gap-2">
                <input type="file" wire:model="fileCsv" class="form-control" accept=".csv,.txt">
                <button type="submit" class="btn btn-outline-primary">Rekonsiliasi</button>
            </form>
            @error('fileCsv')<div class="text-danger mt-1">{{ $message }}</div>@enderror
            @if(!empty($rekonsiliasi))
                <div class="mt-2">
                    <span class="badge bg-success">{{ $rekonsiliasi['matched'] ?? 0 }} cocok</span>
                    <span class="badge bg-warning">{{ count($rekonsiliasi['selisih'] ?? []) }} selisih</span>
                    <span class="badge bg-danger">{{ count($rekonsiliasi['tak_dikenal'] ?? []) }} tak dikenal</span>
                </div>
                @if(!empty($rekonsiliasi['selisih']))
                    <ul class="mt-2 mb-0 small">
                        @foreach(array_slice($rekonsiliasi['selisih'], 0, 10) as $s)
                            <li>{{ $s['reference'] }}: bank Rp {{ number_format($s['bank'], 0, ',', '.') }} vs sistem Rp {{ number_format($s['sistem'], 0, ',', '.') }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(!empty($rekonsiliasi['tak_dikenal']))
                    <p class="mt-2 mb-0 small text-secondary">Tak dikenal: {{ implode(', ', array_slice($rekonsiliasi['tak_dikenal'], 0, 10)) }}</p>
                @endif
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Verifikasi Pembayaran</h3>
            <div class="card-actions d-flex gap-2">
                <input type="text" wire:model.live="refundReason" class="form-control form-control-sm" placeholder="Alasan refund (opsional)" style="width: 220px">
                <select wire:model.live="statusFilter" class="form-select">
                    <option value="pending">pending</option>
                    <option value="verified">verified</option>
                    <option value="rejected">rejected</option>
                    <option value="semua">semua</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>ID</th><th>Nominal</th><th>Status</th><th>Rekonsiliasi</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($pembayarans as $bayar)
                        <tr>
                            <td>#{{ $bayar->id }}<br><small class="text-secondary">{{ $bayar->reference ?? '-' }}</small></td>
                            <td>Rp {{ number_format($bayar->nominal, 0, ',', '.') }}<br><small class="text-secondary">{{ $bayar->metode }}</small></td>
                            <td>
                                <span class="badge bg-{{ $bayar->status === 'verified' ? 'success' : ($bayar->status === 'rejected' ? 'danger' : 'warning') }}">{{ $bayar->status }}</span>
                                @if($bayar->refunded_at)<br><span class="badge bg-dark">refunded</span>@endif
                            </td>
                            <td>@if($bayar->reconciled_at)<span class="badge bg-info">rekonsiliasi</span>@else<span class="text-secondary">-</span>@endif</td>
                            <td class="text-end">
                                <button wire:click="verify({{ $bayar->id }})" class="btn btn-sm btn-outline-success">Verifikasi</button>
                                <button wire:click="reject({{ $bayar->id }})" class="btn btn-sm btn-outline-danger">Tolak</button>
                                @if($bayar->status === 'verified' && !$bayar->refunded_at)
                                    <button wire:click="refund({{ $bayar->id }})" wire:confirm="Refund pembayaran #{{ $bayar->id }}?" class="btn btn-sm btn-outline-warning">Refund</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Tidak ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($pembayarans, 'links'))
            <div class="card-footer">{{ $pembayarans->links() }}</div>
        @endif
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Jurnal Keuangan Terakhir</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Debet</th><th>Kredit</th><th class="text-end">Nominal</th></tr></thead>
                <tbody>
                    @forelse($jurnal as $j)
                        <tr>
                            <td>{{ $j->tanggal instanceof \Carbon\Carbon ? $j->tanggal->format('d-m-Y') : $j->tanggal }}</td>
                            <td><span class="badge bg-{{ $j->jenis === 'refund' ? 'warning' : 'success' }}">{{ $j->jenis }}</span></td>
                            <td>{{ $j->akun_debet }}</td>
                            <td>{{ $j->akun_kredit }}</td>
                            <td class="text-end">Rp {{ number_format($j->nominal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Belum ada jurnal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
