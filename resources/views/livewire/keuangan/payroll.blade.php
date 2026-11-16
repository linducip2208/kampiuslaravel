<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Periode Penggajian</h3></div>
        <div class="card-body d-flex gap-2 align-items-end">
            <select class="form-select" wire:model.live="periodeId" style="max-width:220px">
                @foreach($periodes as $p)
                    <option value="{{ $p->id }}">{{ $p->periode }} ({{ $p->status }})</option>
                @endforeach
            </select>
            <input type="text" class="form-control" wire:model="periodeBaru" placeholder="YYYY-MM" style="max-width:140px">
            <button class="btn btn-outline-primary" wire:click="buatPeriode">Buat</button>
            @if($periode && !$periode->terkunci())
                <button class="btn btn-outline-danger" wire:click="kunci">Kunci Final</button>
            @endif
        </div>
    </div>
    @if($periode && !$periode->terkunci())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Slip Baru</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Pegawai</label>
                        <input type="text" class="form-control mb-1" wire:model.live="cariPegawai" placeholder="Cari nama...">
                        <select class="form-select" wire:model="user_id">
                            <option value="">— pilih —</option>
                            @foreach($pegawais as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pokok</label>
                        <input type="number" class="form-control" wire:model="gaji_pokok" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tunjangan</label>
                        <input type="number" class="form-control" wire:model="tunjangan" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Potongan</label>
                        <input type="number" class="form-control" wire:model="potongan" min="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary" wire:click="simpanSlip">Simpan</button>
                    </div>
                    @error('user_id')<div class="col-12 text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Slip — Total Rp{{ number_format($total, 0, ',', '.') }}</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Pegawai</th><th>Pokok</th><th>Tunjangan</th><th>Potongan</th><th>Bersih</th><th>Status</th><th class="d-print-none">Aksi</th></tr></thead>
                <tbody>
                    @foreach($slips ?? [] as $s)
                        <tr>
                            <td>{{ $s->pegawai->name ?? ('#'.$s->user_id) }}</td>
                            <td>{{ number_format($s->gaji_pokok, 0, ',', '.') }}</td>
                            <td>{{ number_format($s->tunjangan, 0, ',', '.') }}</td>
                            <td>{{ number_format($s->potongan, 0, ',', '.') }}</td>
                            <td><strong>{{ number_format($s->total_bersih, 0, ',', '.') }}</strong></td>
                            <td>{{ $s->status }}</td>
                            <td class="d-print-none"><a href="{{ route('keuangan.slip.cetak', $s->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Cetak</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($slips)<div class="card-footer">{{ $slips->links() }}</div>@endif
    </div>
</div>
