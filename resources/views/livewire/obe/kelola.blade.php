<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">CPL per Prodi</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Prodi</label>
                    <select class="form-select" wire:model.live="prodi_id">
                        <option value="">— pilih —</option>
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kode</label>
                    <input type="text" class="form-control" wire:model="cpl_kode" placeholder="CPL-1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" class="form-control" wire:model="cpl_deskripsi">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary" wire:click="simpanCpl">Simpan CPL</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">CPMK per Matakuliah</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Matakuliah</label>
                    <select class="form-select" wire:model="mk_id">
                        <option value="">— pilih —</option>
                        @foreach($mks as $m)
                            <option value="{{ $m->id }}">{{ $m->kode }} — {{ $m->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPL</label>
                    <select class="form-select" wire:model="cpl_id">
                        <option value="">— pilih —</option>
                        @foreach($cpls as $c)
                            <option value="{{ $c->id }}">{{ $c->kode }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kode CPMK</label>
                    <input type="text" class="form-control" wire:model="cpmk_kode" placeholder="CPMK-1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" class="form-control" wire:model="cpmk_deskripsi">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Bobot</label>
                    <input type="number" class="form-control" wire:model="cpmk_bobot" min="0.1" step="0.1">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="simpanCpmk">Simpan CPMK</button>
                    @error('cpmk_kode')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Petakan Asesmen → CPMK</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">ID CPMK</label>
                    <input type="number" class="form-control" wire:model="asesmen_cpmk_id" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sumber</label>
                    <select class="form-select" wire:model="asesmen_tipe">
                        <option value="quiz_soal">Soal kuis</option>
                        <option value="tugas">Tugas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ID sumber</label>
                    <input type="number" class="form-control" wire:model="asesmen_sumber_id" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bobot</label>
                    <input type="number" class="form-control" wire:model="asesmen_bobot" min="0.1" step="0.1">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-primary" wire:click="petakan">Petakan</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Ketercapaian per Mahasiswa (NIM)</h3></div>
        <div class="card-body d-flex gap-2">
            <input type="text" class="form-control" wire:model="nimRekap" placeholder="NIM" style="max-width:240px">
            @if($rekap)
                <span class="align-self-center">Keseluruhan: <strong>{{ $rekap['keseluruhan'] }}%</strong>
                @foreach($rekap['per_cpl'] as $kode => $nilai) | {{ $kode }}: {{ $nilai }}%@endforeach</span>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>MK</th><th>CPMK</th><th>CPL</th><th>Asesmen</th></tr></thead>
                <tbody>
                    @foreach($cpmks as $c)
                        <tr>
                            <td>{{ $c->matakuliah->kode ?? '-' }}</td>
                            <td>{{ $c->kode }}</td>
                            <td>{{ $c->cpl->kode ?? '-' }}</td>
                            <td>{{ $c->asesmen->count() }} pemetaan</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $cpmks->links() }}</div>
    </div>
</div>
