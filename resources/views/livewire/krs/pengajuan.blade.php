<div>
    @if (session()->has('info'))
        <div class="alert alert-success" role="alert">{{ session('info') }}</div>
    @endif

    @if ($pesanBlokir)
        <div class="alert alert-warning" role="alert">{{ $pesanBlokir }}</div>
    @endif

    <div class="card kampius-card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="ipk">IPK terakhir (otomatis)</label>
                    <input id="ipk" type="number" step="0.01" min="0" max="4" wire:model="ipk" readonly class="form-control" aria-readonly="true">
                    <div class="form-hint">Dihitung sistem dari nilai. Tidak bisa diubah manual.</div>
                </div>
                <div class="col-md-8">
                    <div class="d-flex gap-4">
                        <div><span class="kampius-muted small d-block">Total SKS dipilih</span><strong class="h3 mb-0">{{ $totalSks }}</strong></div>
                        <div><span class="kampius-muted small d-block">Maksimal SKS</span><strong class="h3 mb-0">{{ $sksMaks }}</strong></div>
                        <div><span class="kampius-muted small d-block">Sisa SKS</span><strong class="h3 mb-0">{{ $this->sisaSks }}</strong></div>
                        <div class="ms-auto align-self-center">
                            <span class="badge bg-{{ $totalSks > $sksMaks ? 'danger' : 'green' }}-lt">{{ $totalSks > $sksMaks ? 'Melebihi batas' : 'Dalam batas' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="ajukan">
        <div class="card kampius-card">
            <div class="card-header"><h3 class="card-title">Kelas Tersedia</h3></div>
            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                <table class="table table-vcenter mb-0">
                    <thead class="sticky-top bg-white"><tr><th style="width:48px">Pilih</th><th>Kelas</th><th>Matakuliah</th><th>SKS</th></tr></thead>
                    <tbody>
                        @forelse ($kelasTersedia as $kelas)
                            <tr>
                                <td><input type="checkbox" class="form-check-input" wire:click="toggleKelas({{ $kelas->id }})" @checked(in_array($kelas->id, $kelasDipilih)) aria-label="Pilih kelas {{ $kelas->nama }}"></td>
                                <td><span class="badge bg-blue-lt">{{ $kelas->nama ?? ('Kelas #'.$kelas->id) }}</span></td>
                                <td>{{ $kelas->matakuliah_nama ?? '-' }} <span class="kampius-muted">({{ $kelas->matakuliah_kode ?? '-' }})</span></td>
                                <td>{{ $kelas->sks ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="kampius-empty"><div class="kampius-empty-icon">◫</div><h3>Belum ada kelas tersedia</h3><p class="kampius-muted mb-0">Coba tahun akademik lain atau hubungi BAAK.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                @error('kelasDipilih') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="ajukan">
                    <span wire:loading.remove wire:target="ajukan">Ajukan KRS ({{ count($kelasDipilih) }} kelas)</span>
                    <span wire:loading wire:target="ajukan"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Mengajukan…</span>
                </button>
            </div>
        </div>
    </form>

    @if (isset($waitlistSaya) && $waitlistSaya->count() > 0)
        <div class="card kampius-card mt-3">
            <div class="card-header"><h3 class="card-title">Waiting List Saya</h3></div>
            <div class="list-group list-group-flush">
                @foreach ($waitlistSaya as $wl)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Kelas #{{ $wl->kelas_id }} <span class="badge bg-warning-lt ms-1">{{ $wl->status }}</span></span>
                        @if ($wl->status === 'menunggu')
                            <button type="button" wire:click="keluarWaitlist({{ $wl->kelas_id }})" class="btn btn-sm btn-outline-secondary" wire:confirm="Keluar dari waiting list kelas #{{ $wl->kelas_id }}?">Keluar</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (isset($this->riwayatSaya) && $this->riwayatSaya->count() > 0)
        <div class="card kampius-card mt-3">
            <div class="card-header"><h3 class="card-title">Arsip Pengajuan Saya</h3></div>
            <div class="list-group list-group-flush">
                @foreach ($this->riwayatSaya as $r)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>TA {{ $r->tahunAkademik->tahun ?? '-' }} {{ ucfirst((string) ($r->tahunAkademik->semester ?? '')) }} — {{ $r->total_sks }} SKS <span class="badge bg-info-lt ms-1">{{ $r->status }}</span></span>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('krs.cetak', $r->id) }}">Unduh</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
