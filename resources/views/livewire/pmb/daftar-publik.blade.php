<div>
    @if($noDaftarBerhasil)
        <div class="alert alert-success" role="alert">
            <h4 class="alert-title">Pendaftaran berhasil!</h4>
            Nomor pendaftaran Anda: <strong>{{ $noDaftarBerhasil }}</strong>.
            @if($vaBerhasil)
                <br>VA biaya pendaftaran: <strong>{{ $vaBerhasil }}</strong>.
            @endif
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
            <select id="gelombang_id" wire:model.live="gelombang_id" class="form-select">
                <option value="">— Pilih gelombang —</option>
                @foreach($gelombangs as $gelombang)
                    <option value="{{ $gelombang->id }}">
                        {{ $gelombang->nama }}
                        @if($gelombang->kuota > 0)
                            (sisa {{ $gelombang->sisaKuota() }}/{{ $gelombang->kuota }})
                        @endif
                        — Rp {{ number_format($gelombang->biaya, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
            @error('gelombang_id')<div class="text-danger">{{ $message }}</div>@enderror
            @if($gelombangTerpilih)
                <small class="form-hint">
                    Biaya pendaftaran: Rp {{ number_format($gelombangTerpilih->biaya, 0, ',', '.') }}
                    @if($gelombangTerpilih->kuota > 0)
                        · Sisa kuota: {{ $gelombangTerpilih->sisaKuota() }}/{{ $gelombangTerpilih->kuota }}
                    @endif
                </small>
            @endif
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="referral_kode">Kode Referral Sekolah (opsional)</label>
                <input id="referral_kode" type="text" wire:model="referral_kode" class="form-control" placeholder="cth: SMAN1-001">
                @error('referral_kode')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="beasiswa_id">Beasiswa PMB (opsional)</label>
                <select id="beasiswa_id" wire:model="beasiswa_id" class="form-select">
                    <option value="">— Tanpa beasiswa —</option>
                    @foreach($beasiswas as $bea)
                        <option value="{{ $bea->id }}">{{ $bea->nama }} ({{ $bea->jenis }}: {{ $bea->nilai }})</option>
                    @endforeach
                </select>
                @error('beasiswa_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="prodi1_id">Pilihan Prodi 1</label>
                <select id="prodi1_id" wire:model="prodi1_id" class="form-select">
                    <option value="">— Pilih prodi —</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->nama }} ({{ $prodi->jenjang }})</option>
                    @endforeach
                </select>
                @error('prodi1_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="prodi2_id">Pilihan Prodi 2 (opsional)</label>
                <select id="prodi2_id" wire:model="prodi2_id" class="form-select">
                    <option value="">— Tidak ada —</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->nama }} ({{ $prodi->jenjang }})</option>
                    @endforeach
                </select>
                @error('prodi2_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        <h4>Berkas Pendukung</h4>
        <p class="text-muted">Seret &amp; letakkan file ke kotak, atau klik untuk memilih. Ijazah/KK: PDF/JPG maks 5MB. Foto: JPG maks 2MB.</p>
        <div class="row">
            @foreach(['berkas_ijazah' => ['label' => 'Ijazah', 'hint' => 'PDF/JPG, maks 5MB'], 'berkas_kk' => ['label' => 'Kartu Keluarga', 'hint' => 'PDF/JPG, maks 5MB'], 'berkas_foto' => ['label' => 'Pas Foto', 'hint' => 'JPG, maks 2MB']] as $prop => $meta)
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="{{ $prop }}">{{ $meta['label'] }} ({{ $meta['hint'] }})</label>
                    <div class="dropzone border rounded p-3 text-center"
                         ondrop="event.preventDefault()"
                         ondragover="event.preventDefault()"
                         onclick="document.getElementById('{{ $prop }}').click()"
                         style="cursor:pointer; border-style:dashed !important;">
                        <input id="{{ $prop }}" type="file" wire:model="{{ $prop }}" class="d-none">
                        @if($this->{$prop})
                            <span class="badge bg-green">{{ $this->{$prop}->getClientOriginalName() }}</span>
                            <button type="button" class="btn btn-sm btn-link text-danger" wire:click="hapusBerkas('{{ $prop }}')">hapus</button>
                        @else
                            <span class="text-muted">Seret file ke sini / klik untuk pilih</span>
                        @endif
                        <div wire:loading wire:target="{{ $prop }}"><small>Mengunggah…</small></div>
                    </div>
                    @error($prop)<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary w-100">Daftar</button>
    </form>
</div>
