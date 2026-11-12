<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <form wire:submit="save" class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Judul Skripsi</label>
                <select wire:model="judul_id" class="form-select">
                    <option value="">— Pilih judul —</option>
                    @foreach($juduls as $judul)
                        <option value="{{ $judul->id }}">{{ $judul->judul }}</option>
                    @endforeach
                </select>
                @error('judul_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan bimbingan</label>
                <textarea wire:model="catatan" class="form-control" rows="3"></textarea>
                @error('catatan')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Tanggal</label>
                <input type="date" wire:model="tanggal" class="form-control">
                @error('tanggal')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Simpan Log</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Catatan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->catatan }}</td>
                            <td><span class="badge bg-{{ $log->status === 'disetujui' ? 'success' : ($log->status === 'revisi' ? 'warning' : 'secondary') }}">{{ $log->status }}</span></td>
                            <td class="text-end">
                                <button wire:click="setujui({{ $log->id }})" class="btn btn-sm btn-outline-success">Setujui</button>
                                <button wire:click="revisi({{ $log->id }})" class="btn btn-sm btn-outline-warning">Revisi</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Belum ada log bimbingan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($logs, 'links'))
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
