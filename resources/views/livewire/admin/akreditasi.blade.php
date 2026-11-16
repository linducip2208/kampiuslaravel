<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Dokumen Akreditasi</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Prodi</label>
                    <select class="form-select" wire:model="prodi_id">
                        <option value="">— pilih —</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->jenjang }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" class="form-control" wire:model="kategori" placeholder="mis. borang-3a">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Judul</label>
                    <input type="text" class="form-control" wire:model="judul">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Tautan</label>
                    <input type="url" class="form-control" wire:model="tautan" placeholder="https://...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="diajukan">Diajukan</option>
                        <option value="terverifikasi">Terverifikasi</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
                @error('judul')<div class="col-12 text-danger">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Draf Borang per Prodi (ekspor CSV, bukan klaim akreditasi)</h3></div>
        <div class="card-body d-flex flex-wrap gap-2">
            @foreach($prodis as $p)
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.akreditasi.borang', $p->id) }}">Borang {{ $p->nama }}</a>
            @endforeach
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Siklus PPEMPMTI</h3></div>        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Prodi</label>
                    <select class="form-select" wire:model="siklusProdiId">
                        <option value="">— pilih —</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahun</label>
                    <input type="number" class="form-control" wire:model="siklusTahun" min="2000" max="2100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahap</label>
                    <select class="form-select" wire:model="siklusTahap">
                        <option value="penetapan">Penetapan</option>
                        <option value="pelaksanaan">Pelaksanaan</option>
                        <option value="evaluasi">Evaluasi</option>
                        <option value="pengendalian">Pengendalian</option>
                        <option value="peningkatan">Peningkatan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="siklusStatus">
                        <option value="belum">Belum</option>
                        <option value="sedang">Sedang</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpanSiklus">Simpan Tahap</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>Prodi</th><th>Tahun</th><th>Tahap</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($siklus as $s)
                        <tr>
                            <td>{{ $s->prodi->nama ?? '-' }}</td>
                            <td>{{ $s->tahun }}</td>
                            <td>{{ ucfirst($s->tahap) }}</td>
                            <td><span class="badge bg-{{ $s->status === 'selesai' ? 'green' : ($s->status === 'sedang' ? 'yellow' : 'secondary') }}-lt">{{ $s->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Belum ada siklus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Prodi</th><th>Kategori</th><th>Judul</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($dokumens as $d)
                        <tr>
                            <td>{{ $d->prodi->nama ?? '-' }}</td>
                            <td>{{ $d->kategori }}</td>
                            <td>{{ $d->judul }}</td>
                            <td>{{ $d->status }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-success" wire:click="verifikasi({{ $d->id }})">Verifikasi</button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $d->id }})">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $dokumens->links() }}</div>
    </div>
</div>
