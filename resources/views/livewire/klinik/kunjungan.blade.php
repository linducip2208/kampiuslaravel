<div>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Kunjungan Baru</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">NIM pasien</label>
                    <input type="text" class="form-control" wire:model="nim" placeholder="NIM">
                    @error('nim')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label">Keluhan</label>
                    <input type="text" class="form-control" wire:model="keluhan" placeholder="Keluhan utama">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Diagnosa</label>
                    <input type="text" class="form-control" wire:model="diagnosa">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tindakan</label>
                    <input type="text" class="form-control" wire:model="tindakan">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @if($detail)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Rekam #{{ $detail->id }} — {{ $detail->mahasiswa->nim ?? '-' }}</h3></div>
            <div class="card-body">
                <p class="mb-1"><strong>Keluhan:</strong> {{ $detail->keluhan }}</p>
                <p class="mb-1"><strong>Diagnosa:</strong> {{ $detail->diagnosa ?? '-' }}</p>
                <p class="mb-3"><strong>Tindakan:</strong> {{ $detail->tindakan ?? '-' }}</p>
                <h4>Resep</h4>
                <ul class="mb-3">
                    @forelse($detail->resep as $r)
                        <li>{{ $r->obat }} — {{ $r->dosis ?? '-' }} × {{ $r->jumlah ?? '-' }}</li>
                    @empty
                        <li class="text-secondary">Belum ada resep.</li>
                    @endforelse
                </ul>
                <div class="row g-2">
                    <div class="col-md-4"><input type="text" class="form-control" wire:model="obat" placeholder="Nama obat"></div>
                    <div class="col-md-3"><input type="text" class="form-control" wire:model="dosis" placeholder="Dosis"></div>
                    <div class="col-md-3"><input type="text" class="form-control" wire:model="jumlah" placeholder="Jumlah"></div>
                    <div class="col-md-2"><button class="btn btn-outline-primary" wire:click="simpanResep">Tambah</button></div>
                </div>
                <button class="btn btn-sm btn-outline-secondary mt-3" wire:click="tutup">Tutup</button>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Antrean / Riwayat Kunjungan</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>Tanggal</th><th>NIM</th><th>Nama</th><th>Keluhan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($antrean as $a)
                        <tr>
                            <td>{{ $a->tanggal?->format('d M Y') }}</td>
                            <td>{{ $a->mahasiswa->nim ?? '-' }}</td>
                            <td>{{ $a->mahasiswa->user->name ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($a->keluhan, 50) }}</td>
                            <td><button class="btn btn-sm btn-outline-primary" wire:click="lihat({{ $a->id }})">Buka</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $antrean->links() }}</div>
    </div>
</div>
