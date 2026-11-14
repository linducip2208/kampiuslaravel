<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="row row-cards">
        <div class="col-lg-4">
            <form wire:submit="simpan" class="card kampius-card mb-3">
                <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} Soal</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="bs-mk">Mata kuliah</label>
                        <select id="bs-mk" wire:model="matakuliah_id" class="form-select @error('matakuliah_id') is-invalid @enderror">
                            <option value="">— Pilih —</option>
                            @foreach($matakuliahs as $mk)
                                <option value="{{ $mk->id }}">{{ $mk->kode }} — {{ $mk->nama }}</option>
                            @endforeach
                        </select>
                        @error('matakuliah_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select wire:model.live="jenis" class="form-select">
                            <option value="pg">Pilihan ganda</option>
                            <option value="esai">Esai</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="bs-tanya">Pertanyaan</label>
                        <textarea id="bs-tanya" wire:model="pertanyaan" rows="3" class="form-control @error('pertanyaan') is-invalid @enderror"></textarea>
                        @error('pertanyaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if($jenis === 'pg')
                        <div class="mb-3">
                            <label class="form-label">Opsi jawaban (minimal 2)</label>
                            @foreach($opsi as $i => $o)
                                <div class="input-group mb-1">
                                    <span class="input-group-text">{{ chr(65 + $i) }}</span>
                                    <input type="text" wire:model="opsi.{{ $i }}" class="form-control" placeholder="Opsi {{ chr(65 + $i) }}">
                                </div>
                            @endforeach
                            @error('opsi')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="bs-kunci">Kunci (A-F, kosongkan bila belum ada)</label>
                            <input id="bs-kunci" type="text" wire:model="kunci" maxlength="1" class="form-control @error('kunci') is-invalid @enderror" placeholder="A">
                            @error('kunci')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="bs-bobot">Bobot</label>
                        <input id="bs-bobot" type="number" wire:model="bobot" min="1" max="100" class="form-control">
                        @error('bobot')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3"><label class="form-check"><input type="checkbox" wire:model="aktif" class="form-check-input"><span class="form-check-label">Aktif</span></label></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="simpan">
                            <span wire:loading.remove wire:target="simpan">Simpan</span>
                            <span wire:loading wire:target="simpan"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyimpan…</span>
                        </button>
                        @if($editId)<button type="button" wire:click="batal" class="btn btn-outline-secondary">Batal</button>@endif
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card kampius-card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                    <h3 class="card-title">Bank Soal</h3>
                    <div class="ms-auto d-flex gap-2">
                        <select wire:model.live="filterMk" class="form-select form-select-sm" style="max-width:200px" aria-label="Filter mata kuliah">
                            <option value="">Semua MK</option>
                            @foreach($matakuliahs as $mk)
                                <option value="{{ $mk->id }}">{{ $mk->kode }}</option>
                            @endforeach
                        </select>
                        <input type="search" wire:model.live.debounce.400ms="search" class="form-control form-control-sm" style="max-width:200px" placeholder="Cari soal…" aria-label="Cari soal">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead><tr><th>MK</th><th>Soal</th><th>Jenis</th><th>Kunci</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($soals as $s)
                                <tr>
                                    <td><span class="badge bg-blue-lt">{{ $s->matakuliah->kode ?? '-' }}</span></td>
                                    <td class="text-truncate" style="max-width:280px">{{ $s->pertanyaan }}</td>
                                    <td><span class="badge bg-secondary-lt">{{ strtoupper($s->jenis) }}</span></td>
                                    <td>{{ $s->kunci ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $s->aktif ? 'success' : 'secondary' }}">{{ $s->aktif ? 'aktif' : 'nonaktif' }}</span></td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $s->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                        <button wire:click="hapus({{ $s->id }})" wire:confirm="Hapus soal #{{ $s->id }} dari bank?" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Bank masih kosong</h3><p class="kampius-muted mb-0">Tambahkan soal pertama lewat formulir di samping.</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $soals->links() }}</div>
            </div>
        </div>
    </div>
</div>
