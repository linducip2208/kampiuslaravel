<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Cari topik / bidang</label>
                <input type="text" wire:model.live="cari" class="form-control" placeholder="misal: machine learning">
            </div>
        </div>
    </div>
    <form wire:submit="save" class="card mb-3">
        <div class="card-header"><h3 class="card-title">Tawarkan Topik (Dosen)</h3></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Judul topik</label>
                <input type="text" wire:model="judul" class="form-control">
                @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea wire:model="deskripsi" class="form-control" rows="2"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bidang</label>
                    <input type="text" wire:model="bidang" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kuota</label>
                    <input type="number" min="1" max="20" wire:model="kuota" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Topik</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Topik</th><th>Bidang</th><th>Sisa</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($topiks as $topik)
                        <tr>
                            <td>{{ $topik->judul }}</td>
                            <td>{{ $topik->bidang ?? '-' }}</td>
                            <td>{{ $topik->sisa }} / {{ $topik->kuota }}</td>
                            <td><span class="badge bg-secondary">{{ $topik->status }}</span></td>
                            <td class="text-end">
                                <button wire:click="ambil({{ $topik->id }})" class="btn btn-sm btn-outline-primary">Ambil</button>
                                <button wire:click="tutup({{ $topik->id }})" class="btn btn-sm btn-outline-secondary">Tutup</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Belum ada topik.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($topiks, 'links'))
            <div class="card-footer">{{ $topiks->links() }}</div>
        @endif
    </div>
</div>
