<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Karier Saya</h3></div>
        <div class="card-body">
            @if(!$alumni)
                <div class="alert alert-info">Data alumni belum tersedia (dibuat otomatis saat yudisium).</div>
            @else
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" class="form-control" wire:model="pekerjaan">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Instansi</label>
                        <input type="text" class="form-control" wire:model="instansi">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">HP</label>
                        <input type="text" class="form-control" wire:model="hp">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <label class="form-check"><input type="checkbox" class="form-check-input" wire:model="bersedia_tracer"> Bersedia dihubungi tracer study</label>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
