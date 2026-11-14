<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Anggaran</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tahun akademik</label>
                    <select class="form-select" wire:model.live="tahun_akademik_id">
                        @foreach($tahuns as $t)
                            <option value="{{ $t->id }}">{{ $t->tahun }} · {{ ucfirst($t->semester) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kode (jenis tagihan)</label>
                    <input type="text" class="form-control" wire:model="kode">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" wire:model="nama">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pagu (Rp)</label>
                    <input type="number" class="form-control" wire:model="pagu" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-control" wire:model="keterangan">
                    @error('kode')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Realisasi vs Pagu</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Kode</th><th>Nama</th><th>Pagu</th><th>Realisasi</th><th>Sisa</th><th>%</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($ringkasan as $row)
                        <tr>
                            <td>{{ $row['anggaran']->kode }}</td>
                            <td>{{ $row['anggaran']->nama }}</td>
                            <td>{{ number_format($row['anggaran']->pagu, 0, ',', '.') }}</td>
                            <td>{{ number_format($row['realisasi'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['sisa'], 0, ',', '.') }}</td>
                            <td>{{ $row['persen'] }}%</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="edit({{ $row['anggaran']->id }})">Ubah</button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $row['anggaran']->id }})">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-secondary">Belum ada anggaran periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
