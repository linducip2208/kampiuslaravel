<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Beban Kerja Dosen — Total Disetujui: {{ $totalSks }} SKS</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tahun akademik</label>
                    <select class="form-select" wire:model.live="tahunId">
                        @foreach($tahuns as $t)
                            <option value="{{ $t->id }}">{{ $t->tahun }} · {{ ucfirst($t->semester) }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!empty($rekap))
                    <div class="col-12 text-secondary">Rekap: @foreach($rekap as $kat => $sks) {{ $kat }}={{ $sks }} SKS; @endforeach</div>
                @endif
                @if($isAdmin && !empty($rekapProdi))
                    <div class="col-12">
                        <strong>Rekap per prodi (disetujui):</strong>
                        @foreach($rekapProdi as $rp)
                            <span class="badge bg-blue-lt me-1">{{ $rp['prodi'] }}: {{ $rp['total_sks'] }} SKS / {{ $rp['dosen'] }} dosen</span>
                        @endforeach
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" wire:model="kategori">
                        <option value="pendidikan">Pendidikan</option>
                        <option value="penelitian">Penelitian</option>
                        <option value="pengabdian">Pengabdian</option>
                        <option value="penunjang">Penunjang</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Uraian</label>
                    <input type="text" class="form-control" wire:model="uraian">
                </div>
                <div class="col-md-2">
                    <label class="form-label">SKS</label>
                    <input type="number" class="form-control" wire:model="sks" min="1" max="20">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bukti (tautan/nomor)</label>
                    <input type="text" class="form-control" wire:model="bukti">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" wire:click="ajukan">Ajukan</button>
                    <button class="btn btn-outline-secondary" wire:click="sinkron">Sinkron Mengajar</button>
                    @if($isAdmin)
                        <button class="btn btn-outline-success" wire:click="terbitkanSk">Terbitkan SK</button>
                    @endif
                    @error('uraian')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Dosen</th><th>Kategori</th><th>Uraian</th><th>SKS</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($bebans as $b)
                        <tr>
                            <td>{{ $b->dosen->user->name ?? ('#'.$b->dosen_id) }}</td>
                            <td>{{ $b->kategori }}</td>
                            <td>{{ $b->uraian }}</td>
                            <td>{{ $b->sks }}</td>
                            <td>{{ $b->status }}</td>
                            <td>
                                @if($isAdmin && $b->status === 'diajukan')
                                    <button class="btn btn-sm btn-outline-success" wire:click="setujui({{ $b->id }})">Setujui</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="tolak({{ $b->id }})">Tolak</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $bebans->links() }}</div>
    </div>
</div>
