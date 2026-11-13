<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($error)
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
    @endif
    <form wire:submit="simpan" class="row g-2 mb-3">
        <div class="col-md-4">
            <label class="form-label">Pertemuan</label>
            <select wire:model.live="pertemuan_id" class="form-select">
                <option value="">— Pilih —</option>
                @foreach($daftar as $p)<option value="{{ $p->id }}">{{ $p->kelas?->matakuliah?->nama }} — ke-{{ $p->ke }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status Dosen</label>
            <select wire:model="status_hadir" class="form-select">
                <option value="hadir">hadir</option>
                <option value="izin">izin</option>
                <option value="sakit">sakit</option>
                <option value="alfa">alfa</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Realisasi</label>
            <input type="text" wire:model="realisasi" class="form-control" placeholder="Otamatis bila kosong">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kendala</label>
            <input type="text" wire:model="kendala" class="form-control">
        </div>
        <div class="col-12"><button type="submit" class="btn btn-primary">Simpan Presensi + BAP</button></div>
    </form>
    @if($bap)
        <div class="card"><div class="card-body">
            <h4>{{ $bap['judul'] }}</h4>
            <pre class="mb-0">{{ $bap['isi'] }}</pre>
        </div></div>
    @endif
</div>
