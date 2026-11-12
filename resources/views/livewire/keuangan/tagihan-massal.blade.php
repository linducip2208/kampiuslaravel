<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <form wire:submit="generate" class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tahun akademik</label>
                    <select wire:model="tahun_id" class="form-select">
                        <option value="">— Pilih tahun akademik —</option>
                        @foreach($tahuns as $tahun)
                            <option value="{{ $tahun->id }}">{{ $tahun->tahun }} {{ $tahun->semester }}</option>
                        @endforeach
                    </select>
                    @error('tahun_id')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jenis</label>
                    <select wire:model="jenis" class="form-select">
                        <option value="UKT">UKT</option>
                        <option value="SPP">SPP</option>
                        <option value="PMB">PMB</option>
                        <option value="lain">lain</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nominal</label>
                    <input type="number" wire:model="nominal" class="form-control" placeholder="Nominal">
                    @error('nominal')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Prodi</label>
                    <select wire:model="prodi_id" class="form-select">
                        <option value="">Semua prodi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Angkatan (opsional)</label>
                    <input type="text" wire:model="angkatan" class="form-control" placeholder="cth. 2024">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Tagihan</button>
        </div>
    </form>
</div>
