<div>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif
    @if($error)<div class="alert alert-danger">{{ $error }}</div>@endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Pinjam Baru</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label">Barcode eksemplar</label>
                    <input type="text" class="form-control" wire:model="barcode" placeholder="cth. BK-0001" autofocus>
                    @error('barcode')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">ID user peminjam</label>
                    <input type="number" class="form-control" wire:model="userId" min="1">
                    @error('userId')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" wire:click="pinjam">Pinjam (14 hari)</button>
                </div>
            </div>
            <div class="text-secondary small mt-2">Maks 3 pinjaman aktif per user · denda Rp1.000/hari keterlambatan.</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Sedang Dipinjam</h3>
            <input type="text" class="form-control w-auto" wire:model.live.debounce.500ms="cari" placeholder="Cari barcode...">
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Barcode</th><th>Judul</th><th>Peminjam</th><th>Jatuh Tempo</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($aktif as $p)
                        <tr>
                            <td><code>{{ $p->eksemplar->barcode ?? '-' }}</code></td>
                            <td>{{ \Illuminate\Support\Str::limit($p->eksemplar->buku->judul ?? '-', 50) }}</td>
                            <td>{{ $p->peminjam->name ?? ('#'.$p->user_id) }}</td>
                            <td>{{ $p->jatuh_tempo?->format('Y-m-d') }}</td>
                            <td>
                                @if($p->jatuh_tempo && $p->jatuh_tempo->isPast())
                                    <span class="badge bg-red-lt">terlambat</span>
                                @else
                                    <span class="badge bg-green-lt">berjalan</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-success" wire:click="kembali({{ $p->id }}, 'baik')">Kembali</button>
                                <button class="btn btn-sm btn-outline-warning" wire:click="kembali({{ $p->id }}, 'rusak')">Rusak</button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="kembali({{ $p->id }}, 'hilang')">Hilang</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-secondary">Tidak ada pinjaman aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $aktif->links() }}</div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Koleksi Terbaru</h3></div>
        <div class="card-body">
            <ul class="mb-0">
                @foreach($bukuBaru as $b)
                    <li>{{ $b->kode }} — {{ $b->judul }} <span class="text-secondary">({{ $b->pengarang ?? 'tanpa pengarang' }})</span></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
