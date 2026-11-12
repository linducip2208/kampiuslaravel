<div>
    @if($noDaftarBerhasil)
        <div class="alert alert-success" role="alert">
            <h4 class="alert-title">Pendaftaran berhasil!</h4>
            Nomor pendaftaran Anda: <strong>{{ $noDaftarBerhasil }}</strong>.
            Simpan nomor ini untuk mengecek pengumuman kelulusan.
        </div>
    @endif

    <form wire:submit="submit">
        <div class="mb-3">
            <label class="form-label" for="nama">Nama Lengkap</label>
            <input id="nama" type="text" wire:model="nama" class="form-control">
            @error('nama')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" type="email" wire:model="email" class="form-control">
                @error('email')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="hp">No. HP</label>
                <input id="hp" type="text" wire:model="hp" class="form-control" placeholder="08xxxxxxxxxx">
                @error('hp')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="sekolah_asal">Sekolah Asal</label>
            <input id="sekolah_asal" type="text" wire:model="sekolah_asal" class="form-control">
            @error('sekolah_asal')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="gelombang_id">Gelombang PMB</label>
            <select id="gelombang_id" wire:model="gelombang_id" class="form-select">
                <option value="">— Pilih gelombang —</option>
                @foreach($gelombangs as $gelombang)
                    <option value="{{ $gelombang->id }}">{{ $gelombang->nama }}</option>
                @endforeach
            </select>
            @error('gelombang_id')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="prodi1_id">Pilihan Prodi 1 (ID)</label>
                <input id="prodi1_id" type="number" wire:model="prodi1_id" min="1" class="form-control">
                @error('prodi1_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="prodi2_id">Pilihan Prodi 2 (ID, opsional)</label>
                <input id="prodi2_id" type="number" wire:model="prodi2_id" min="1" class="form-control">
                @error('prodi2_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        <h4>Berkas Pendukung</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="berkas_ijazah">Ijazah (PDF/JPG, maks 5MB)</label>
                <input id="berkas_ijazah" type="file" wire:model="berkas_ijazah" class="form-control">
                @error('berkas_ijazah')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="berkas_kk">Kartu Keluarga (PDF/JPG, maks 5MB)</label>
                <input id="berkas_kk" type="file" wire:model="berkas_kk" class="form-control">
                @error('berkas_kk')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="berkas_foto">Pas Foto (JPG, maks 2MB)</label>
                <input id="berkas_foto" type="file" wire:model="berkas_foto" class="form-control">
                @error('berkas_foto')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Daftar</button>
    </form>
</div>
