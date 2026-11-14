<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Pemasok Baru</h3></div>
        <div class="card-body d-flex gap-2">
            <input type="text" class="form-control" wire:model="pemasokNama" placeholder="Nama pemasok">
            <button class="btn btn-outline-primary" wire:click="simpanPemasok">Tambah</button>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Hutang Baru</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Pemasok</label>
                    <select class="form-select" wire:model="pemasok_id">
                        <option value="">— pilih —</option>
                        @foreach($pemasoks as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nomor</label>
                    <input type="text" class="form-control" wire:model="nomor">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Uraian</label>
                    <input type="text" class="form-control" wire:model="uraian">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nominal (Rp)</label>
                    <input type="number" class="form-control" wire:model="nominal" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jatuh tempo</label>
                    <input type="date" class="form-control" wire:model="jatuh_tempo">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                    @error('nomor')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Nomor</th><th>Pemasok</th><th>Nominal</th><th>Terbayar</th><th>Sisa</th><th>Status</th><th>Bayar</th></tr></thead>
                <tbody>
                    @foreach($hutangs as $h)
                        <tr>
                            <td>{{ $h->nomor }}</td>
                            <td>{{ $h->pemasok->nama ?? '-' }}</td>
                            <td>{{ number_format($h->nominal, 0, ',', '.') }}</td>
                            <td>{{ number_format($h->terbayar, 0, ',', '.') }}</td>
                            <td>{{ number_format($h->sisa(), 0, ',', '.') }}</td>
                            <td>{{ $h->status }}</td>
                            <td>
                                @if($h->status !== 'lunas')
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" placeholder="Rp" wire:model="bayarNominal" min="1">
                                        <button class="btn btn-outline-success" wire:click="bayar({{ $h->id }})">Bayar</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $hutangs->links() }}</div>
    </div>
</div>
