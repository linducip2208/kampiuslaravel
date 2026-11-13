<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @error('yudisium')<div class="alert alert-warning">{{ $message }}</div>@enderror
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Yudisium Kelulusan</h3>
            <div class="card-actions"><input type="search" wire:model.live="search" class="form-control" placeholder="Cari NIM..."></div>
        </div>
        <div class="card-body">
            <p class="text-secondary">Syarat otomatis dicek: sidang lulus, bebas tunggakan, IPK ≥ 2.00. Lulus otomatis terbit SKL + profil alumni.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($mahasiswa as $m)
                        <tr>
                            <td>{{ $m->nim }}</td><td>{{ $m->user->name ?? '-' }}</td>
                            <td>{{ $m->prodi->nama ?? '-' }}</td><td><span class="badge bg-secondary">{{ $m->status }}</span></td>
                            <td class="text-end"><button wire:click="luluskan({{ $m->id }})" class="btn btn-sm btn-success" onclick="return confirm('Luluskan NIM {{ $m->nim }}?')">Luluskan</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Tidak ada kandidat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $mahasiswa->links() }}</div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Batch Wisuda, Toga &amp; Buku Wisuda</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3"><input wire:model="batchNama" class="form-control" placeholder="Nama batch (mis. Wisuda 72)"></div>
                <div class="col-md-2"><input wire:model="batchPeriode" class="form-control" placeholder="Periode (2026/2027)"></div>
                <div class="col-md-2"><input wire:model="batchTanggal" type="date" class="form-control"></div>
                <div class="col-md-3"><input wire:model="batchLokasi" class="form-control" placeholder="Lokasi"></div>
                <div class="col-md-2"><button wire:click="buatBatch" class="btn btn-primary w-100">Buka Batch</button></div>
            </div>
            <div class="mt-2 d-flex gap-2 align-items-center">
                <select wire:model.live="batchId" class="form-select w-auto">
                    <option value="">— Pilih batch —</option>
                    @foreach($batches as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->periode }}) — {{ $b->status }}</option>
                    @endforeach
                </select>
                @if($batchId)
                    <button wire:click="tutupBatch" class="btn btn-sm btn-outline-secondary">Tutup batch</button>
                @endif
            </div>
        </div>
        @if($peserta)
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Kursi</th><th>NIM / Nama</th><th>No. Ijazah</th><th>PIN</th><th>Toga</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($peserta as $p)
                        <tr>
                            <td>{{ $p->no_kursi }}</td>
                            <td>{{ $p->mahasiswa->nim ?? '-' }} / {{ $p->mahasiswa->user->name ?? '-' }}</td>
                            <td>{{ $p->no_ijazah ?? '-' }}</td>
                            <td><code>{{ $p->pin_ijazah ?? '-' }}</code></td>
                            <td><span class="badge bg-secondary">{{ $p->status_toga }}</span></td>
                            <td class="text-end">
                                <button wire:click="updateToga({{ $p->id }}, 'pinjam')" class="btn btn-sm btn-outline-primary">Pinjam</button>
                                <button wire:click="updateToga({{ $p->id }}, 'kembali')" class="btn btn-sm btn-outline-success">Kembali</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-secondary">Belum ada peserta (buku wisuda kosong).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $peserta->links('pagination::bootstrap-5', ['paginator' => $peserta]) }}</div>
        @endif
    </div>

    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Lulusan Siap Wisuda (SKL terbit)</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>NIM</th><th>Nama</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($lulusan as $l)
                        <tr>
                            <td>{{ $l->nim }}</td><td>{{ $l->user->name ?? '-' }}</td>
                            <td class="text-end"><button wire:click="daftarkanWisuda({{ $l->id }})" class="btn btn-sm btn-primary">Masuk batch</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Belum ada lulusan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
