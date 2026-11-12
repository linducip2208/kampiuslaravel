<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <form wire:submit="save" class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Jenis surat</label>
                <select wire:model="jenis" class="form-select">
                    <option value="aktif_kuliah">Surat aktif kuliah</option>
                    <option value="pengantar_kp">Pengantar KP</option>
                    <option value="keterangan_lulus">Keterangan lulus</option>
                    <option value="legalisir">Legalisir</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Keperluan</label>
                <textarea wire:model="keperluan" class="form-control" rows="2"></textarea>
                @error('keperluan')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Ajukan</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Jenis</th><th>Nomor</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($riwayat as $surat)
                        <tr><td>{{ $surat->jenis }}</td><td>{{ $surat->nomor_surat ?? '-' }}</td><td><span class="badge bg-secondary">{{ $surat->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Belum ada pengajuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
