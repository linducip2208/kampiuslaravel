<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Hibah Riset</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label">Judul</label>
                    <input type="text" class="form-control" wire:model="judul">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Skema</label>
                    <input type="text" class="form-control" wire:model="skema">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sumber dana</label>
                    <input type="text" class="form-control" wire:model="sumber_dana">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <input type="text" class="form-control" wire:model="tahun" placeholder="2026">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total dana (Rp)</label>
                    <input type="number" class="form-control" wire:model="total_dana" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="diajukan">Diajukan</option>
                        <option value="berjalan">Berjalan</option>
                        <option value="selesai">Selesai</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                    @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Catat Pencairan</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label">Hibah</label>
                    <select class="form-select" wire:model="cairHibahId">
                        <option value="">— pilih —</option>
                        @foreach($hibahs as $h)
                            <option value="{{ $h->id }}">{{ $h->judul }} ({{ $h->tahun }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahap</label>
                    <input type="number" class="form-control" wire:model="cairTahap" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" class="form-control" wire:model="cairNominal" min="1">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary" wire:click="cair">Catat</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Tahun</th><th>Total</th><th>Cair</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($hibahs as $h)
                        <tr>
                            <td>{{ $h->judul }}</td>
                            <td>{{ $h->tahun }}</td>
                            <td>{{ number_format($h->total_dana, 0, ',', '.') }}</td>
                            <td>{{ number_format($h->totalCair(), 0, ',', '.') }}</td>
                            <td>{{ $h->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $hibahs->links() }}</div>
    </div>
</div>
