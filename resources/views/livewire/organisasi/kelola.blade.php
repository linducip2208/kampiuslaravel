<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Organisasi Kemahasiswaan</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" wire:model="nama">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" wire:model="jenis">
                        <option value="bem">BEM</option>
                        <option value="hima">HIMA</option>
                        <option value="ukm">UKM</option>
                        <option value="komunitas">Komunitas</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pembina (dosen)</label>
                    <select class="form-select" wire:model="pembina_dosen_id">
                        <option value="">—</option>
                        @foreach($dosens as $d)
                            <option value="{{ $d->id }}">{{ $d->user->name ?? ('Dosen #'.$d->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nomor SK (otomatis bila kosong)</label>
                    <input type="text" class="form-control" wire:model="nomorSk" placeholder="SK-ORG/...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" class="form-control" wire:model="deskripsi">
                    @error('nama')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Tambah Anggota (per NIM)</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label">Organisasi</label>
                    <select class="form-select" wire:model="organisasiAnggotaId">
                        <option value="">— pilih —</option>
                        @foreach($organisasis as $o)
                            <option value="{{ $o->id }}">{{ $o->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIM</label>
                    <input type="text" class="form-control" wire:model="nimAnggota" placeholder="NIM mahasiswa">
                    @error('nimAnggota')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary" wire:click="tambahAnggota">Tambah</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Nama</th><th>Nomor SK</th><th>Jenis</th><th>Anggota</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($organisasis as $o)
                        <tr>
                            <td>{{ $o->nama }}</td>
                            <td>{{ $o->nomor_sk ?? '—' }}</td>
                            <td>{{ strtoupper($o->jenis) }}</td>
                            <td>{{ $o->anggota_count }}</td>
                            <td>{{ $o->aktif ? 'aktif' : 'nonaktif' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="toggleAktif({{ $o->id }})">Aktif/Non</button>
                            </td>
                        </tr>
                    @endforeach
                    @if($organisasis->isEmpty())
                        <tr><td colspan="6" class="text-secondary">Belum ada organisasi.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $organisasis->links() }}</div>
    </div>
</div>
