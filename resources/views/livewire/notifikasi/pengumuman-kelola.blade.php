<div>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Tulis Pengumuman</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label" for="pg-judul">Judul</label>
                    <input type="text" id="pg-judul" class="form-control" wire:model="judul">
                    @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="pg-target">Target</label>
                    <select id="pg-target" class="form-select" wire:model="target">
                        <option value="semua">Semua</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="pg-prodi">Prodi (opsional)</label>
                    <select id="pg-prodi" class="form-select" wire:model="prodi_id">
                        <option value="">—</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="pg-isi">Isi</label>
                    <textarea id="pg-isi" class="form-control" rows="3" wire:model="isi"></textarea>
                    @error('isi')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="simpan">Simpan Draf</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Daftar Pengumuman</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>Judul</th><th>Target</th><th>Prodi</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($pengumumans as $p)
                        <tr>
                            <td>{{ $p->judul }}</td>
                            <td>{{ $p->target }}</td>
                            <td>{{ $p->prodi->nama ?? '—' }}</td>
                            <td>
                                @if($p->published_at)
                                    <span class="badge bg-green-lt">terbit {{ $p->published_at->format('d M Y') }}</span>
                                @else
                                    <span class="badge bg-secondary-lt">draf</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $p->id }})">Ubah</button>
                                @if($p->published_at)
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="tarik({{ $p->id }})">Tarik</button>
                                @else
                                    <button class="btn btn-sm btn-success" wire:click="terbitkan({{ $p->id }})">Terbitkan</button>
                                @endif
                                <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $p->id }})" wire:confirm="Hapus pengumuman ini?">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Belum ada pengumuman. Tulis draf pertama di atas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="push-topik" wire:model="pushTopik">
                <label class="form-check-label" for="push-topik">Push ke topik FCM prodi saat menerbitkan (bila prodi diisi)</label>
            </div>
            <div>{{ $pengumumans->links() }}</div>
        </div>
    </div>
</div>
