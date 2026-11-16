<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Lowongan Baru</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Judul</label>
                    <input type="text" class="form-control" wire:model="judul">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit</label>
                    <input type="text" class="form-control" wire:model="unit">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" wire:model="jenis">
                        <option value="dosen">Dosen</option>
                        <option value="tendik">Tendik</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control" rows="2" wire:model="deskripsi"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kuota</label>
                    <input type="number" class="form-control" wire:model="kuota" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tutup</label>
                    <input type="date" class="form-control" wire:model="tgl_tutup">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="buka">Buka</option>
                        <option value="tutup">Tutup</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
                @error('judul')<div class="col-12 text-danger">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Jenis</th><th>Pelamar</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($lowongans as $l)
                        <tr>
                            <td>{{ $l->judul }}</td>
                            <td>{{ strtoupper($l->jenis) }}</td>
                            <td>{{ $l->lamaran_count }}</td>
                            <td>{{ $l->status }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" wire:click="$set('lihatLowonganId', {{ $l->id }})">Pelamar</button>
                            </td>
                        </tr>
                    @endforeach
                    @if($lowongans->isEmpty())
                        <tr><td colspan="5" class="text-secondary">Belum ada lowongan.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $lowongans->links() }}</div>
    </div>
    @if($pelamar)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Pelamar</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Nama</th><th>Email</th><th>HP</th><th>Status</th><th>Onboarding</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach($pelamar as $p)
                            <tr>
                                <td>{{ $p->nama }}</td>
                                <td>{{ $p->email }}</td>
                                <td>{{ $p->hp }}</td>
                                <td>{{ $p->status }}</td>
                                <td>
                                    @if($p->status === 'diterima')
                                        @foreach($p->onboarding as $o)
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="onb-{{ $o->id }}" wire:change="toggleOnboarding({{ $o->id }})" @checked($o->selesai)>
                                                <label class="form-check-label {{ $o->selesai ? 'text-decoration-line-through text-secondary' : '' }}" for="onb-{{ $o->id }}">{{ $o->item }}</label>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-success" wire:click="ubahStatusLamaran({{ $p->id }}, 'diterima')">Terima</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="ubahStatusLamaran({{ $p->id }}, 'ditolak')">Tolak</button>
                                </td>
                            </tr>
                        @endforeach
                        @if($pelamar->isEmpty())
                            <tr><td colspan="6" class="text-secondary">Belum ada pelamar untuk lowongan ini.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $pelamar->links() }}</div>
        </div>
    @endif
</div>
