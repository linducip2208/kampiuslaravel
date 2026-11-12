<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @error('form.hapus')<div class="alert alert-warning">{{ $message }}</div>@enderror
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($definisi as $key => $def)
                    <button wire:click="gantiEntitas('{{ $key }}')" class="btn btn-sm {{ $entitas === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $def['judul'] }}</button>
                @endforeach
            </div>
        </div>
    </div>
    <div class="row row-cards">
        <div class="col-lg-4">
            <form wire:submit="simpan" class="card">
                <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} {{ $judul }}</h3></div>
                <div class="card-body">
                    @foreach($kolom as $field => $tipe)
                        <div class="mb-3">
                            <label class="form-label">{{ str_replace('_', ' ', $field) }}</label>
                            @if($tipe === 'fakultas')
                                <select wire:model="form.{{ $field }}" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($fakultas as $f)<option value="{{ $f->id }}">{{ $f->nama }}</option>@endforeach
                                </select>
                            @elseif($tipe === 'prodi')
                                <select wire:model="form.{{ $field }}" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($prodis as $p)<option value="{{ $p->id }}">{{ $p->nama }}</option>@endforeach
                                </select>
                            @elseif($tipe === 'matakuliah')
                                <select wire:model="form.{{ $field }}" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($matakuliahs as $m)<option value="{{ $m->id }}">{{ $m->kode }} — {{ $m->nama }}</option>@endforeach
                                </select>
                            @elseif($tipe === 'tahun')
                                <select wire:model="form.{{ $field }}" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($tahuns as $t)<option value="{{ $t->id }}">{{ $t->tahun }} {{ $t->semester }}</option>@endforeach
                                </select>
                            @elseif($tipe === 'bool')
                                <label class="form-check"><input type="checkbox" wire:model="form.{{ $field }}" value="1" class="form-check-input"><span class="form-check-label">Aktif</span></label>
                            @elseif($tipe === 'number')
                                <input type="number" wire:model="form.{{ $field }}" class="form-control" min="0">
                            @elseif($tipe === 'time')
                                <input type="time" wire:model="form.{{ $field }}" class="form-control">
                            @else
                                <input type="text" wire:model="form.{{ $field }}" class="form-control">
                            @endif
                            @error('form.'.$field)<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
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
                    <h3 class="card-title">Daftar {{ $judul }}</h3>
                    <div class="card-actions">
                        <input type="search" wire:model.live="search" class="form-control" placeholder="Cari nama...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>ID</th><th>Nama / Kode</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->nama ?? $row->kode ?? $row->tahun ?? '-' }}</td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $row->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                        <button wire:click="hapus({{ $row->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-secondary">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</div>
