<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <form wire:submit="save" class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Judul skripsi</label>
                <input type="text" wire:model="judul" class="form-control">
                @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea wire:model="deskripsi" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Ajukan</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($riwayat as $item)
                        <tr><td>{{ $item->judul }}</td><td><span class="badge bg-secondary">{{ $item->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="2" class="text-secondary">Belum ada pengajuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
