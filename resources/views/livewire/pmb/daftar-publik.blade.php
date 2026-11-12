<div>
    @if ($noDaftarBerhasil)
        <div style="border:1px solid #2fb344;padding:12px;margin-bottom:12px;">
            <strong>Pendaftaran berhasil!</strong>
            Nomor pendaftaran Anda: <strong>{{ $noDaftarBerhasil }}</strong>.
            Simpan nomor ini untuk mengecek pengumuman kelulusan.
        </div>
    @endif

    <form wire:submit="submit">
        <div>
            <label for="nama">Nama Lengkap</label>
            <input id="nama" type="text" wire:model="nama">
            @error('nama') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email">Email</label>
            <input id="email" type="email" wire:model="email">
            @error('email') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="hp">No. HP</label>
            <input id="hp" type="text" wire:model="hp" placeholder="08xxxxxxxxxx">
            @error('hp') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="sekolah_asal">Sekolah Asal</label>
            <input id="sekolah_asal" type="text" wire:model="sekolah_asal">
            @error('sekolah_asal') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="gelombang_id">Gelombang PMB</label>
            <select id="gelombang_id" wire:model="gelombang_id">
                <option value="">-- Pilih gelombang --</option>
                @foreach ($gelombangs as $gelombang)
                    <option value="{{ $gelombang->id }}">{{ $gelombang->nama }}</option>
                @endforeach
            </select>
            @error('gelombang_id') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="prodi1_id">Pilihan Prodi 1 (ID)</label>
            <input id="prodi1_id" type="number" wire:model="prodi1_id" min="1">
            @error('prodi1_id') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="prodi2_id">Pilihan Prodi 2 (ID, opsional)</label>
            <input id="prodi2_id" type="number" wire:model="prodi2_id" min="1">
            @error('prodi2_id') <span>{{ $message }}</span> @enderror
        </div>

        <button type="submit">Daftar</button>
    </form>
</div>
