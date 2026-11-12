<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Verifikasi Pembayaran</h3>
            <div class="card-actions">
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
                <thead><tr><th>ID</th><th>Nominal</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($pembayarans as $bayar)
                        <tr>
                            <td>#{{ $bayar->id }}</td>
                            <td>Rp {{ number_format($bayar->nominal, 0, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $bayar->status === 'verified' ? 'success' : ($bayar->status === 'rejected' ? 'danger' : 'warning') }}">{{ $bayar->status }}</span></td>
                            <td class="text-end">
                                <button wire:click="verify({{ $bayar->id }})" class="btn btn-sm btn-outline-success">Verifikasi</button>
                                <button wire:click="reject({{ $bayar->id }})" class="btn btn-sm btn-outline-danger">Tolak</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Tidak ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($pembayarans, 'links'))
            <div class="card-footer">{{ $pembayarans->links() }}</div>
        @endif
    </div>
</div>
