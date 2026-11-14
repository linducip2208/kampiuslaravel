<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Pengabdian Masyarakat</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Judul kegiatan</label>
                    <input type="text" class="form-control" wire:model="judul">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lokasi</label>
                    <input type="text" class="form-control" wire:model="lokasi">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mulai</label>
                    <input type="date" class="form-control" wire:model="tanggal_mulai">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Selesai</label>
                    <input type="date" class="form-control" wire:model="tanggal_selesai">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dana (Rp)</label>
                    <input type="number" class="form-control" wire:model="dana" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="rencana">Rencana</option>
                        <option value="berjalan">Berjalan</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Ringkasan laporan</label>
                    <input type="text" class="form-control" wire:model="laporan_ringkas">
                    @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Lokasi</th><th>Dana</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($items as $p)
                        <tr>
                            <td>{{ $p->judul }}</td>
                            <td>{{ $p->lokasi }}</td>
                            <td>{{ number_format($p->dana, 0, ',', '.') }}</td>
                            <td>{{ $p->status }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $p->id }})">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
</div>
