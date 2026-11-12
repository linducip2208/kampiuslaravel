<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="row row-cards">
        <div class="col-lg-4">
            <form wire:submit="simpan" class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} Beasiswa</h3></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Nama</label><input type="text" wire:model="nama" class="form-control">@error('nama')<div class="text-danger">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Jenis</label>
                        <select wire:model="jenis" class="form-select"><option value="persen">Persen (%)</option><option value="nominal">Nominal (Rp)</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nilai</label><input type="number" wire:model="nilai" min="1" class="form-control">@error('nilai')<div class="text-danger">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Kuota (opsional)</label><input type="number" wire:model="kuota" min="1" class="form-control"></div>
                    <div class="mb-3"><label class="form-check"><input type="checkbox" wire:model="aktif" class="form-check-input"><span class="form-check-label">Aktif</span></label></div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
            <form wire:submit="assign" class="card">
                <div class="card-header"><h3 class="card-title">Tetapkan ke Mahasiswa</h3></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Beasiswa</label>
                        <select wire:model="assignBeasiswaId" class="form-select"><option value="">— Pilih —</option>
                            @foreach(\App\Models\Beasiswa::where('aktif', true)->get() as $b)<option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->jenis }} {{ $b->nilai }})</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">NIM</label><input type="text" wire:model="assignNim" class="form-control" placeholder="NIM mahasiswa"></div>
                    <button type="submit" class="btn btn-outline-primary">Tetapkan</button>
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Nama</th><th>Jenis</th><th>Nilai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($beasiswas as $b)
                                <tr>
                                    <td>{{ $b->nama }}</td><td>{{ $b->jenis }}</td><td>{{ $b->jenis === 'persen' ? $b->nilai.'%' : 'Rp '.number_format($b->nilai, 0, ',', '.') }}</td>
                                    <td><span class="badge bg-{{ $b->aktif ? 'success' : 'secondary' }}">{{ $b->aktif ? 'aktif' : 'nonaktif' }}</span></td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $b->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                        <button wire:click="hapus({{ $b->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus beasiswa?')">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-secondary">Belum ada beasiswa.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $beasiswas->links() }}</div>
            </div>
        </div>
    </div>
</div>
