<div>
    <form wire:submit="save" class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Judul tugas</label>
                <input type="text" wire:model="judul" class="form-control" placeholder="Judul tugas">
                @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea wire:model="deskripsi" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Deadline</label>
                <input type="datetime-local" wire:model="deadline" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Deadline</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->judul }}</td><td>{{ $item->deadline }}</td>
                            <td class="text-end">
                                <button wire:click="edit({{ $item->id }})" class="btn btn-sm btn-outline-primary">Edit</button>
                                <button wire:click="delete({{ $item->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus tugas ini?')">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Belum ada tugas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($items, 'links'))
            <div class="card-footer">{{ $items->links() }}</div>
        @endif
    </div>
</div>
