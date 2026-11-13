<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @error('form.hapus')<div class="alert alert-warning">{{ $message }}</div>@enderror
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <button wire:click="gantiTab('dosen')" class="btn btn-sm {{ $tab === 'dosen' ? 'btn-primary' : 'btn-outline-primary' }}">Dosen</button>
                <button wire:click="gantiTab('tendik')" class="btn btn-sm {{ $tab === 'tendik' ? 'btn-primary' : 'btn-outline-primary' }}">Tendik</button>
            </div>
        </div>
    </div>
    <div class="row row-cards">
        <div class="col-lg-4">
            <form wire:submit="simpan" class="card">
                <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} {{ $tab === 'dosen' ? 'Dosen' : 'Tendik' }}</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" wire:model="form.nama" class="form-control">
                        @error('form.nama')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" wire:model="form.email" class="form-control">
                        @error('form.email')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    @if($tab === 'dosen')
                        <div class="mb-3">
                            <label class="form-label">NIDN</label>
                            <input type="text" wire:model="form.nidn" class="form-control">
                            @error('form.nidn')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Program Studi</label>
                            <select wire:model="form.prodi_id" class="form-select">
                                <option value="">— Pilih —</option>
                                @foreach($prodis as $p)<option value="{{ $p->id }}">{{ $p->nama }}</option>@endforeach
                            </select>
                            @error('form.prodi_id')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select wire:model="form.status" class="form-select">
                                <option value="">— Pilih —</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            @error('form.status')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label">Peran</label>
                            <select wire:model="form.role" class="form-select">
                                <option value="">— Pilih —</option>
                                @foreach($peranTendik as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                            </select>
                            @error('form.role')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        @if($editId)
                            <button type="button" wire:click="$set('editId', null); $set('form', [])" class="btn btn-outline-secondary">Batal</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar {{ $tab === 'dosen' ? 'Dosen' : 'Tendik' }}</h3>
                    <div class="card-actions">
                        <input type="search" wire:model.live="search" class="form-control" placeholder="Cari {{ $tab === 'dosen' ? 'nama / NIDN' : 'nama / email' }}...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>ID</th><th>Nama</th><th>{{ $tab === 'dosen' ? 'NIDN / Prodi / Status' : 'Email / Peran' }}</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $tab === 'dosen' ? ($row->user?->name ?? '-') : $row->name }}</td>
                                    <td>
                                        @if($tab === 'dosen')
                                            <span class="badge bg-secondary-lt">{{ $row->nidn }}</span>
                                            <div class="text-secondary">{{ $row->prodi?->nama ?? '-' }}</div>
                                            <span class="badge {{ $row->status === 'aktif' ? 'bg-green-lt' : 'bg-red-lt' }}">{{ $row->status }}</span>
                                        @else
                                            <div>{{ $row->email }}</div>
                                            <span class="badge bg-blue-lt">{{ $row->roles->first()?->name ?? '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $row->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                        <button wire:click="hapus({{ $row->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-secondary">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</div>
