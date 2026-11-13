<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <form wire:submit="generate" class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tahun akademik</label>
                    <select wire:model.live="tahun_id" class="form-select">
                        <option value="">— Pilih tahun akademik —</option>
                        @foreach($tahuns as $tahun)
                            <option value="{{ $tahun->id }}">{{ $tahun->tahun }} {{ $tahun->semester }}</option>
                        @endforeach
                    </select>
                    @error('tahun_id')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jenis</label>
                    <select wire:model="jenis" class="form-select">
                        <option value="UKT">UKT</option>
                        <option value="SPP">SPP</option>
                        <option value="PMB">PMB</option>
                        <option value="lain">lain</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nominal (0 = pakai template UKT)</label>
                    <input type="number" wire:model="nominal" class="form-control" placeholder="Nominal">
                    @error('nominal')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Prodi</label>
                    <select wire:model="prodi_id" class="form-select">
                        <option value="">Semua prodi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Angkatan (opsional)</label>
                    <input type="text" wire:model="angkatan" class="form-control" placeholder="cth. 2024">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Jalur masuk (opsional)</label>
                    <input type="text" wire:model="jalur" class="form-control" placeholder="cth. SNBP / SNBT / Mandiri">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Jatuh tempo (opsional)</label>
                    <input type="date" wire:model="jatuh_tempo" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cicilan</label>
                    <input type="number" wire:model="cicilan" class="form-control" min="1" max="12">
                    @error('cicilan')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="generate">
                <span wire:loading.remove wire:target="generate">Generate Tagihan</span>
                <span wire:loading wire:target="generate"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Memproses…</span>
            </button>
            <button type="button" wire:click="terapkanDenda" class="btn btn-warning ms-2" wire:loading.attr="disabled" wire:target="terapkanDenda">
                <span wire:loading.remove wire:target="terapkanDenda">Terapkan Denda Otomatis</span>
                <span wire:loading wire:target="terapkanDenda"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Memproses…</span>
            </button>
            <p class="text-muted mt-2 mb-0">Nominal 0 memakai template UKT per prodi/angkatan/jalur. Potongan beasiswa diterapkan otomatis.</p>
        </div>
    </form>

    <div class="card">
        <div class="card-header fw-bold">Dashboard Tunggakan</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3"><div class="fs-4 fw-bold">{{ $tunggakan['menunggak'] }}</div><div class="text-muted">Tagihan menunggak</div></div>
                <div class="col-md-3"><div class="fs-4 fw-bold">Rp {{ number_format($tunggakan['total_sisa'], 0, ',', '.') }}</div><div class="text-muted">Total sisa (termasuk denda)</div></div>
                <div class="col-md-3"><div class="fs-4 fw-bold">Rp {{ number_format($tunggakan['total_denda'], 0, ',', '.') }}</div><div class="text-muted">Total denda</div></div>
                <div class="col-md-3"><div class="fs-4 fw-bold">Rp {{ number_format($tunggakan['total_terbayar'], 0, ',', '.') }}</div><div class="text-muted">Sudah terbayar</div></div>
            </div>
            <hr>
            <table class="table table-sm">
                <thead><tr><th>Prodi</th><th class="text-end">Tagihan</th><th class="text-end">Mahasiswa</th><th class="text-end">Sisa</th></tr></thead>
                <tbody>
                    @forelse($tunggakan['per_prodi'] as $nama => $row)
                        <tr><td>{{ $nama }}</td><td class="text-end">{{ $row['tagihan'] }}</td><td class="text-end">{{ $row['mahasiswa'] }}</td><td class="text-end">Rp {{ number_format($row['sisa'], 0, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Tidak ada tunggakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
