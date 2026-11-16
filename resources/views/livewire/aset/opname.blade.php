<div>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Opname Aset</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Aset</label>
                    <select class="form-select" wire:model="asetId">
                        <option value="">— Pilih aset —</option>
                        @foreach($asets as $a)
                            <option value="{{ $a->id }}">{{ $a->kode }} — {{ $a->nama }} ({{ $a->kondisi }})</option>
                        @endforeach
                    </select>
                    @error('asetId')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kondisi fisik</label>
                    <select class="form-select" wire:model="kondisiFisik">
                        <option value="baik">baik</option>
                        <option value="rusak_ringan">rusak ringan</option>
                        <option value="rusak_berat">rusak berat</option>
                        <option value="hilang">hilang</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" class="form-control" wire:model="catatan" placeholder="opsional">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpan">Simpan Opname</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Riwayat Opname (cocok: {{ $cocok }} dari {{ $riwayat->total() }})</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Waktu</th><th>Kode</th><th>Buku</th><th>Fisik</th><th>Hasil</th></tr></thead>
                <tbody>
                    @foreach($riwayat as $r)
                        <tr>
                            <td>{{ $r->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $r->aset->kode ?? '-' }}</td>
                            <td>{{ $r->kondisi_buku }}</td>
                            <td>{{ $r->kondisi_fisik }}</td>
                            <td><span class="badge bg-{{ $r->cocok() ? 'green' : 'red' }}-lt">{{ $r->cocok() ? 'cocok' : 'selisih' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $riwayat->links() }}</div>
    </div>
</div>
