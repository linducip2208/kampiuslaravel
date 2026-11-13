<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="row row-cards">
        <div class="col-lg-4">
            <form wire:submit="simpan" class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} Aturan Denda</h3></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Tahun akademik (opsional = global)</label>
                        <select wire:model="tahun_akademik_id" class="form-select"><option value="">Global</option>
                            @foreach($tahuns as $t)<option value="{{ $t->id }}">{{ $t->tahun }} {{ $t->semester }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Jenis tagihan</label>
                        <select wire:model="jenis" class="form-select"><option value="UKT">UKT</option><option value="SPP">SPP</option><option value="PMB">PMB</option><option value="lain">Lain</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Terlambat minimal (hari)</label><input type="number" wire:model="terlambat_hari_min" min="1" max="365" class="form-control">@error('terlambat_hari_min')<div class="text-danger">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Mode</label>
                        <select wire:model="mode" class="form-select"><option value="persen">Persen (%)</option><option value="nominal">Nominal (Rp)</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nilai{{ $mode === 'persen' ? ' (maks 100%)' : '' }}</label><input type="number" wire:model="nilai" min="1" class="form-control">@error('nilai')<div class="text-danger">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Maksimal denda Rp (opsional)</label><input type="number" wire:model="maksimal" min="0" class="form-control"></div>
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
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>TA</th><th>Jenis</th><th>Min. Telat</th><th>Denda</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($rules as $r)
                                <tr>
                                    <td>{{ $r->tahunAkademik?->tahun ?? 'Global' }}</td><td>{{ $r->jenis }}</td><td>{{ $r->terlambat_hari_min }} hari</td>
                                    <td>{{ $r->mode === 'persen' ? $r->nilai.'%' : 'Rp '.number_format($r->nilai, 0, ',', '.') }}{{ $r->maksimal !== null ? ' (maks Rp '.number_format($r->maksimal, 0, ',', '.').')' : '' }}</td>
                                    <td><span class="badge bg-{{ $r->aktif ? 'success' : 'secondary' }}">{{ $r->aktif ? 'aktif' : 'nonaktif' }}</span></td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $r->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                        <button wire:click="hapus({{ $r->id }})" wire:confirm="Hapus aturan denda #{{ $r->id }}?" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-secondary">Belum ada aturan denda. Tambahkan agar denda otomatis berjalan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $rules->links() }}</div>
            </div>
        </div>
    </div>
</div>
