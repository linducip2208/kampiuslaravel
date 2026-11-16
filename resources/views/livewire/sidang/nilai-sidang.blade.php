<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($rataRata !== null)
        <div class="alert alert-info" role="alert">Nilai akhir otomatis: {{ $rataRata }} ({{ $hurufMutu }})</div>
    @endif
    @if($beritaAcara)
        <div class="alert alert-success" role="alert">Berita acara: {{ $beritaAcara }}</div>
    @endif
    <form wire:submit.prevent="addPenguji" class="card mb-3">
        <div class="card-header"><h3 class="card-title">Nilai Penguji</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sidang</label>
                    <select wire:model="sidang_id" class="form-select">
                        <option value="">— Pilih sidang —</option>
                        @foreach($sidangs as $sidang)
                            <option value="{{ $sidang->id }}">#{{ $sidang->id }} {{ $sidang->jenis }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Dosen (internal)</label>
                    <select wire:model="dosen_id" class="form-select">
                        <option value="">— Pilih dosen —</option>
                        @foreach($dosens as $dosen)
                            <option value="{{ $dosen->id }}">{{ $dosen->nidn }}</option>
                        @endforeach
                    </select>
                    @error('dosen_id')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">atau Penguji eksternal (nama)</label>
                    <input type="text" wire:model="namaEksternal" class="form-control" placeholder="Nama penguji luar kampus">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Peran</label>
                    <input type="text" wire:model="peran" class="form-control" placeholder="pembimbing/penguji">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nilai</label>
                    <input type="number" step="0.01" wire:model="nilai" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Nilai</button>
        </div>
    </form>
    <form wire:submit.prevent="setStatus" class="card">
        <div class="card-body d-flex gap-2 align-items-end">
            <div class="flex-fill">
                <label class="form-label">Status sidang</label>
                <select wire:model="status" class="form-select">
                    <option value="lulus">lulus</option>
                    <option value="revisi">revisi</option>
                    <option value="gagal">gagal</option>
                    <option value="terdaftar">terdaftar</option>
                </select>
            </div>
            <button type="submit" class="btn btn-outline-primary">Update Status</button>
        </div>
    </form>
</div>
