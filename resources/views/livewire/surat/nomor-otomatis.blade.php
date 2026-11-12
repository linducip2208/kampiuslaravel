<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Penomoran Surat</h3>
            <div class="card-actions">
                <select wire:model.live="statusFilter" class="form-select">
                    <option value="diajukan">diajukan</option>
                    <option value="diproses">diproses</option>
                    <option value="selesai">selesai</option>
                    <option value="ditolak">ditolak</option>
                    <option value="semua">semua</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>ID</th><th>Jenis</th><th>Nomor</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($surats as $surat)
                        <tr>
                            <td>#{{ $surat->id }}</td><td>{{ $surat->jenis }}</td>
                            <td>{{ $surat->nomor_surat ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $surat->status }}</span></td>
                            <td class="text-end">
                                <button wire:click="proses({{ $surat->id }})" class="btn btn-sm btn-outline-primary">Terbitkan Nomor</button>
                                <button wire:click="selesaikan({{ $surat->id }})" class="btn btn-sm btn-outline-success">Selesai</button>
                                <button wire:click="tolak({{ $surat->id }})" class="btn btn-sm btn-outline-danger">Tolak</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Tidak ada surat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($surats, 'links'))
            <div class="card-footer">{{ $surats->links() }}</div>
        @endif
    </div>
</div>
