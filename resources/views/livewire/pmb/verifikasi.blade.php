<div>
    @if (session()->has('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" wire:model.live="search" placeholder="Cari nama / no. daftar / email" class="form-control">
        </div>
        <div class="col-md-2">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterGelombang" class="form-select" aria-label="Filter gelombang">
                <option value="">Semua gelombang</option>
                @foreach ($gelombangs as $g)
                    <option value="{{ $g->id }}">{{ $g->nama }} (kuota {{ $g->kuota }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" wire:model="ambang" class="form-control" min="0" max="100" step="0.5" aria-label="Ambang kelulusan perangkingan">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-primary w-100" wire:click="prosesRanking" wire:confirm="Jalankan perangkingan gelombang terpilih?">Ranking</button>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-vcenter">
        <thead>
            <tr>
                <th>No. Daftar</th>
                <th>Nama</th>
                <th>Gelombang / Kuota</th>
                <th>Nilai CBT / Wawancara / Rata-rata</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendaftars as $pendaftar)
                <tr>
                    <td>{{ $pendaftar->no_daftar }}</td>
                    <td>{{ $pendaftar->nama }}<br><small class="text-muted">{{ $pendaftar->email }} · {{ $pendaftar->prodi1?->nama ?? 'Prodi '.$pendaftar->prodi1_id }}</small></td>
                    <td>{{ $pendaftar->gelombang?->nama ?? '-' }}<br><small class="text-muted">lulus {{ $pendaftar->gelombang?->lulusCount() ?? 0 }}/{{ $pendaftar->gelombang?->kuota ?? 0 }}</small></td>
                    <td>
                        CBT: {{ $pendaftar->nilaiJenis('cbt') ?? '-' }} ·
                        Wawancara: {{ $pendaftar->nilaiJenis('wawancara') ?? '-' }} ·
                        <strong>{{ $pendaftar->nilaiAkhir() ?? '-' }}</strong>
                    </td>
                    <td><span class="badge bg-secondary">{{ $pendaftar->status }}</span></td>
                    <td>
                        <div class="btn-list">
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="pilih({{ $pendaftar->id }})">Detail</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="ubahStatus({{ $pendaftar->id }}, 'diverifikasi')">Verifikasi</button>
                            <button type="button" class="btn btn-sm btn-success" wire:click="ubahStatus({{ $pendaftar->id }}, 'lulus')">Lulus</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="ubahStatus({{ $pendaftar->id }}, 'tidak')">Tidak Lulus</button>
                            <button type="button" class="btn btn-sm btn-primary" wire:click="tentukanKelulusan({{ $pendaftar->id }})">Otomatis (≥60)</button>
                            <button type="button" class="btn btn-sm btn-dark" wire:click="daftarUlang({{ $pendaftar->id }})">Daftar Ulang</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada pendaftar.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    {{ $pendaftars->links() }}

    @if ($selectedId)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Verifikasi &amp; Nilai #{{ $selectedId }}</h3></div>
            <div class="card-body">
                <label class="form-label" for="catatan">Catatan verifikasi (wajib untuk penolakan)</label>
                <textarea id="catatan" wire:model="catatan" class="form-control"></textarea>
                @error('catatan') <span class="text-danger">{{ $message }}</span> @enderror
                @error('selectedId') <span class="text-danger">{{ $message }}</span> @enderror
                <div class="btn-list mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="ubahStatus({{ $selectedId }}, 'diverifikasi')">Verifikasi</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="ubahStatus({{ $selectedId }}, 'ditolak')">Tolak</button>
                </div>

                <hr>
                <h4>Scoring seleksi (0–100)</h4>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label" for="nilaiCbt">Nilai CBT saringan</label>
                        <input id="nilaiCbt" type="number" min="0" max="100" step="0.01" wire:model="nilaiCbt" class="form-control">
                        @error('nilaiCbt') <span class="text-danger">{{ $message }}</span> @enderror
                        <button type="button" class="btn btn-sm btn-primary mt-2" wire:click="simpanNilai({{ $selectedId }}, 'cbt')">Simpan CBT</button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="nilaiWawancara">Nilai wawancara</label>
                        <input id="nilaiWawancara" type="number" min="0" max="100" step="0.01" wire:model="nilaiWawancara" class="form-control">
                        @error('nilaiWawancara') <span class="text-danger">{{ $message }}</span> @enderror
                        <button type="button" class="btn btn-sm btn-primary mt-2" wire:click="simpanNilai({{ $selectedId }}, 'wawancara')">Simpan Wawancara</button>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="catatanNilai">Catatan nilai</label>
                        <input id="catatanNilai" type="text" wire:model="catatanNilai" class="form-control">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-success mt-3" wire:click="tentukanKelulusan({{ $selectedId }})">Tentukan kelulusan otomatis</button>
            </div>
        </div>
    @endif
</div>
