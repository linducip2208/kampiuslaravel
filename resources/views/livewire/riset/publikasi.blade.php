<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Publikasi &amp; HKI</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label">Judul</label>
                    <input type="text" class="form-control" wire:model="judul">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" wire:model="jenis">
                        <option value="jurnal">Jurnal</option>
                        <option value="prosiding">Prosiding</option>
                        <option value="buku">Buku</option>
                        <option value="hki">HKI</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Penulis</label>
                    <input type="text" class="form-control" wire:model="penulis">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penerbit</label>
                    <input type="text" class="form-control" wire:model="penerbit">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Tahun</label>
                    <input type="text" class="form-control" wire:model="tahun" placeholder="2026">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="diajukan">Diajukan</option>
                        <option value="terbit">Terbit</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">DOI / tautan</label>
                    <input type="text" class="form-control" wire:model="doi">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
                @error('judul')<div class="col-12 text-danger">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Jenis</th><th>Tahun</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($items as $p)
                        <tr>
                            <td>{{ $p->judul }}</td>
                            <td>{{ strtoupper($p->jenis) }}</td>
                            <td>{{ $p->tahun }}</td>
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
