<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tracer Study Alumni</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">NIM</label>
                    <input type="text" class="form-control" wire:model="nim" placeholder="NIM saat kuliah">
                    @error('nim')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status saat ini</label>
                    <select class="form-select" wire:model="status_kerja">
                        <option value="bekerja">Bekerja</option>
                        <option value="wirausaha">Wirausaha</option>
                        <option value="kuliah_lanjut">Kuliah lanjut</option>
                        <option value="mencari_kerja">Mencari kerja</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rentang gaji</label>
                    <select class="form-select" wire:model="rentang_gaji">
                        <option value="">—</option>
                        <option value="<3jt">&lt; Rp3 jt</option>
                        <option value="3-6jt">Rp3–6 jt</option>
                        <option value="6-10jt">Rp6–10 jt</option>
                        <option value=">10jt">&gt; Rp10 jt</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Perusahaan</label>
                    <input type="text" class="form-control" wire:model="perusahaan">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jabatan</label>
                    <input type="text" class="form-control" wire:model="jabatan">
                </div>
                <div class="col-12">
                    <label class="form-label">Masukan untuk kampus</label>
                    <textarea class="form-control" rows="3" wire:model="masukan"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="kirim">Kirim Tracer</button>
                </div>
            </div>
        </div>
    </div>
</div>
