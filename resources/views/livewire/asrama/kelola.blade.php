<div>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Kamar Asrama</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Gedung</label>
                    <input type="text" class="form-control" wire:model="gedung" placeholder="Gedung A">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kode</label>
                    <input type="text" class="form-control" wire:model="kode" placeholder="A-101">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kapasitas (bed)</label>
                    <input type="number" class="form-control" wire:model="kapasitas" min="1" max="8">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Iuran / periode</label>
                    <input type="number" class="form-control" wire:model="iuran" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpanKamar">Simpan</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>Gedung</th><th>Kode</th><th>Terisi</th><th>Iuran</th></tr></thead>
                <tbody>
                    @forelse($kamars as $k)
                        <tr>
                            <td>{{ $k->gedung }}</td>
                            <td>{{ $k->kode }}</td>
                            <td>{{ $k->penghuni_count }}/{{ $k->kapasitas }}</td>
                            <td>{{ number_format($k->iuran, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Belum ada kamar. Tambahkan di atas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Alokasi Hunian</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">NIM mahasiswa</label>
                    <input type="text" class="form-control" wire:model="nimHuni" placeholder="NIM">
                    @error('nimHuni')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kamar</label>
                    <select class="form-select" wire:model="kamarHuniId">
                        <option value="">— pilih —</option>
                        @foreach($kamars as $k)
                            <option value="{{ $k->id }}">{{ $k->kode }} ({{ $k->penghuni_count }}/{{ $k->kapasitas }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" wire:click="hunikan">Hunikan</button>
                    <button class="btn btn-outline-secondary" wire:click="tarikIuran" wire:confirm="Tarik iuran asrama periode berjalan untuk semua penghuni?">Tarik Iuran</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>NIM</th><th>Nama</th><th>Kamar</th><th>Masuk</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($hunian as $h)
                        <tr>
                            <td>{{ $h->mahasiswa->nim ?? '-' }}</td>
                            <td>{{ $h->mahasiswa->user->name ?? '-' }}</td>
                            <td>{{ $h->kamar->kode ?? '-' }}</td>
                            <td>{{ $h->masuk_at?->format('d M Y') }}</td>
                            <td><button class="btn btn-sm btn-outline-danger" wire:click="keluarkan({{ $h->id }})" wire:confirm="Keluarkan penghuni ini?">Keluarkan</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Belum ada penghuni.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $hunian->links() }}</div>
    </div>
</div>
