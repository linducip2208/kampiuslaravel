<div>
    @if (session()->has('info'))
        <div class="alert alert-success" role="alert">{{ session('info') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="card kampius-card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="cari-mhs">Cari mahasiswa (ID)</label>
                    <input id="cari-mhs" type="text" wire:model.live="search" placeholder="cth. 20240001" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="catatan">Alasan penolakan (wajib saat menolak)</label>
                    <input id="catatan" type="text" wire:model="catatan" placeholder="Tulis alasan yang jelas untuk mahasiswa" class="form-control @error('catatan') is-invalid @enderror">
                    @error('catatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card kampius-card">
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>ID KRS</th><th>Mahasiswa</th><th>Total SKS</th><th>Detail</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($krsList as $krs)
                        <tr>
                            <td><span class="badge bg-blue-lt">#{{ $krs->id }}</span></td>
                            <td>Mhs #{{ $krs->mahasiswa_id }}</td>
                            <td>{{ $krs->total_sks }} SKS</td>
                            <td><span class="badge bg-secondary-lt">{{ $krs->details->count() }} kelas</span></td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end">
                                    <button type="button" wire:click="setujui({{ $krs->id }})" wire:confirm="Setujui KRS #{{ $krs->id }}?" wire:loading.attr="disabled" wire:target="setujui" class="btn btn-sm btn-success">Setujui</button>
                                    <button type="button" wire:click="tolak({{ $krs->id }})" wire:confirm="Tolak KRS #{{ $krs->id }}? Pastikan alasan penolakan sudah diisi." wire:loading.attr="disabled" wire:target="tolak" class="btn btn-sm btn-outline-danger">Tolak</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="kampius-empty"><div class="kampius-empty-icon">✓</div><h3>Antrean kosong</h3><p class="kampius-muted mb-0">Tidak ada KRS menunggu persetujuan.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $krsList->links() }}</div>
    </div>
</div>
