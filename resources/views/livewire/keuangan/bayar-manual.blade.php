<div>
    @if($message)
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <form wire:submit="simpan" class="card mb-3">
        <div class="card-header"><h3 class="card-title">Upload Bukti Transfer Manual</h3></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Tagihan</label>
                <select wire:model="tagihan_id" class="form-select">
                    <option value="">— Pilih tagihan —</option>
                    @foreach($tagihan as $t)
                        <option value="{{ $t->id }}">{{ $t->jenis }} — Rp {{ number_format($t->sisa(), 0, ',', '.') }} ({{ $t->status }})</option>
                    @endforeach
                </select>
                @error('tagihan_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Bukti transfer (JPG/PNG/PDF, maks 5MB)</label>
                <input type="file" wire:model="bukti" class="form-control">
                @error('bukti')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Kirim Bukti</button>
        </div>
    </form>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Riwayat Pengajuan</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>ID</th><th>Metode</th><th>Nominal</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($riwayat as $r)
                        <tr>
                            <td>{{ $r->id }}</td><td>{{ $r->metode }}</td>
                            <td>Rp {{ number_format($r->nominal, 0, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $r->status === 'verified' ? 'success' : ($r->status === 'rejected' ? 'danger' : 'warning') }}">{{ $r->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Belum ada pengajuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
