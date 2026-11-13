<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <label class="form-label mb-0">Prodi</label>
                <select wire:model.live="prodiId" wire:change="gantiProdi($event.target.value)" class="form-select w-auto">
                    <option value="">— Semua —</option>
                    @foreach($prodis as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
                <span class="text-secondary">Kelola versi kurikulum, petakan MK per semester, atur prasyarat/ekivalensi.</span>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-5">
            <form wire:submit="simpanKurikulum" class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ $editKurikulumId ? 'Ubah' : 'Tambah' }} Kurikulum</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Prodi</label>
                        <select wire:model="formKurikulum.prodi_id" class="form-select">
                            <option value="">— Pilih —</option>
                            @foreach($prodis as $p)<option value="{{ $p->id }}">{{ $p->nama }}</option>@endforeach
                        </select>
                        @error('formKurikulum.prodi_id')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" wire:model="formKurikulum.kode" class="form-control" placeholder="KUR2024">
                            @error('formKurikulum.kode')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Tahun berlaku</label>
                            <input type="number" wire:model="formKurikulum.tahun_berlaku" class="form-control" min="2000" max="2100">
                            @error('formKurikulum.tahun_berlaku')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" wire:model="formKurikulum.nama" class="form-control" placeholder="Kurikulum 2024">
                        @error('formKurikulum.nama')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select wire:model="formKurikulum.status" class="form-select">
                            <option value="aktif">aktif</option>
                            <option value="nonaktif">nonaktif</option>
                            <option value="draf">draf</option>
                        </select>
                        @error('formKurikulum.status')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" wire:model="formKurikulum.keterangan" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>

            <form wire:submit="tambahRelasi" class="card">
                <div class="card-header"><h3 class="card-title">Prasyarat / Ekivalensi MK</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Matakuliah</label>
                        <select wire:model="formRelasi.matakuliah_id" class="form-select">
                            <option value="">— Pilih —</option>
                            @foreach($semuaMk as $m)<option value="{{ $m->id }}">{{ $m->kode }} — {{ $m->nama }}</option>@endforeach
                        </select>
                        @error('formRelasi.matakuliah_id')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prasyarat / Setara dengan</label>
                        <select wire:model="formRelasi.prasyarat_id" class="form-select">
                            <option value="">— Pilih —</option>
                            @foreach($semuaMk as $m)<option value="{{ $m->id }}">{{ $m->kode }} — {{ $m->nama }}</option>@endforeach
                        </select>
                        @error('formRelasi.prasyarat_id')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Jenis</label>
                            <select wire:model="formRelasi.jenis" class="form-select">
                                <option value="prasyarat">prasyarat</option>
                                <option value="ekivalensi">ekivalensi</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Keterangan</label>
                            <input type="text" wire:model="formRelasi.keterangan" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan relasi</button>
                    <p class="text-secondary mt-2 mb-0">Siklus prasyarat ditolak otomatis. Ekivalensi dipakai untuk konversi MK antar versi kurikulum.</p>
                </div>
            </form>
        </div>

        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Versi Kurikulum</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Kode</th><th>Nama</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($kurikulums as $k)
                                <tr class="{{ $kurikulumId === $k->id ? 'table-active' : '' }}">
                                    <td>{{ $k->kode }} <span class="text-secondary">({{ $k->tahun_berlaku }})</span></td>
                                    <td>{{ $k->nama }}<div class="text-secondary">{{ $k->prodi->nama ?? '-' }}</div></td>
                                    <td><span class="badge {{ $k->status === 'aktif' ? 'bg-green' : 'bg-secondary' }}">{{ $k->status }}</span></td>
                                    <td class="text-end">
                                        <button wire:click="pilihKurikulum({{ $k->id }})" class="btn btn-sm btn-outline-primary">Pilih</button>
                                        <button wire:click="editKurikulum({{ $k->id }})" class="btn btn-sm btn-outline-secondary">Ubah</button>
                                        <button wire:click="aktifkanKurikulum({{ $k->id }})" class="btn btn-sm btn-outline-success">Aktifkan</button>
                                        <button wire:click="hapusKurikulum({{ $k->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kurikulum ini?')">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-secondary">Belum ada kurikulum.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $kurikulums->links() }}</div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">MK dalam {{ $kurikulumAktif->kode ?? '—' }}</h3></div>
                <div class="card-body">
                    <form wire:submit="tambahMk" class="row g-2">
                        <div class="col-md-5">
                            <select wire:model="formMk.matakuliah_id" class="form-select">
                                <option value="">— Pilih MK —</option>
                                @foreach($semuaMk as $m)<option value="{{ $m->id }}">{{ $m->kode }} — {{ $m->nama }}</option>@endforeach
                            </select>
                            @error('formMk.matakuliah_id')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <input type="number" wire:model="formMk.semester" class="form-control" min="1" max="14" title="Semester">
                        </div>
                        <div class="col-md-3">
                            <select wire:model="formMk.sifat" class="form-select">
                                <option value="wajib">wajib</option>
                                <option value="pilihan">pilihan</option>
                            </select>
                        </div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Semester</th><th>MK</th><th>Sifat</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($items ?? [] as $it)
                                <tr>
                                    <td>{{ $it->semester }}</td>
                                    <td>{{ $it->matakuliah->kode ?? '-' }} — {{ $it->matakuliah->nama ?? '-' }}</td>
                                    <td>{{ $it->sifat }}</td>
                                    <td class="text-end"><button wire:click="hapusMk({{ $it->id }})" class="btn btn-sm btn-outline-danger">Lepas</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-secondary">Pilih kurikulum lalu tambahkan MK.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($items)<div class="card-footer d-flex justify-content-end">{{ $items->links() }}</div>@endif
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Graf prasyarat {{ $kurikulumAktif->kode ?? '' }}</h3></div>
                <div class="card-body">
                    @if($graf['nodes']->isEmpty())
                        <p class="text-secondary mb-0">Belum ada MK dalam kurikulum ini.</p>
                    @else
                        @php
                            $bySem = $graf['nodes']->groupBy('semester')->sortKeys();
                            $pos = [];
                            $maxRows = max(1, $bySem->map->count()->max());
                            $W = 150; $H = 64;
                            foreach ($bySem as $sem => $list) {
                                foreach ($list->values() as $i => $mk) {
                                    $pos[$mk->id] = ['x' => ($sem - 1) * $W + 10, 'y' => $i * $H + 10];
                                }
                            }
                            $svgW = max(1, $bySem->keys()->max() ?? 1) * $W + 140;
                            $svgH = $maxRows * $H + 40;
                            $edges = $graf['prasyarat']->filter(fn($r) => isset($pos[$r->matakuliah_id]) && isset($pos[$r->prasyarat_id]));
                        @endphp
                        <svg width="100%" viewBox="0 0 {{ $svgW }} {{ $svgH }}" role="img" aria-label="Graf prasyarat" style="min-height:120px">
                            <defs>
                                <marker id="panah" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                                    <path d="M0,0 L8,4 L0,8" fill="none" stroke="#667382" stroke-width="1.5"/>
                                </marker>
                            </defs>
                            @foreach($edges as $e)
                                @php($a = $pos[$e->prasyarat_id])
                                @php($b = $pos[$e->matakuliah_id])
                                <line x1="{{ $a['x'] + 120 }}" y1="{{ $a['y'] + 20 }}" x2="{{ $b['x'] }}" y2="{{ $b['y'] + 20 }}" stroke="#667382" stroke-width="1.5" marker-end="url(#panah)"/>
                            @endforeach
                            @foreach($pos as $id => $p)
                                @php($mk = $graf['nodes']->firstWhere('id', $id))
                                <g>
                                    <rect x="{{ $p['x'] }}" y="{{ $p['y'] }}" width="120" height="40" rx="6" fill="#eef3fb" stroke="#206bc4"/>
                                    <text x="{{ $p['x'] + 8 }}" y="{{ $p['y'] + 17 }}" font-size="11" font-weight="bold" fill="#1d273b">{{ Str::limit($mk->kode ?? '', 12) }}</text>
                                    <text x="{{ $p['x'] + 8 }}" y="{{ $p['y'] + 32 }}" font-size="10" fill="#667382">{{ Str::limit($mk->nama ?? '', 16) }}</text>
                                </g>
                            @endforeach
                        </svg>
                        <div class="mt-2">
                            <div class="text-secondary">Legenda: panah dari MK prasyarat ke MK yang mensyaratkan. Ekivalensi tidak digambar sebagai panah prasyarat:</div>
                            <ul class="mb-0">
                                @forelse($graf['ekivalensi'] as $e)
                                    <li>{{ $e->matakuliah->kode ?? '?' }} setara {{ $e->prasyarat->kode ?? '?' }}{{ $e->keterangan ? ' — '.$e->keterangan : '' }}</li>
                                @empty
                                    <li class="text-secondary">Belum ada ekivalensi.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Semua relasi</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>MK</th><th>Jenis</th><th>Relasi ke</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($relasi as $r)
                                <tr>
                                    <td>{{ $r->matakuliah->kode ?? '-' }}</td>
                                    <td><span class="badge {{ $r->jenis === 'ekivalensi' ? 'bg-purple' : 'bg-blue' }}">{{ $r->jenis }}</span></td>
                                    <td>{{ $r->prasyarat->kode ?? '-' }}</td>
                                    <td class="text-end"><button wire:click="hapusRelasi({{ $r->id }})" class="btn btn-sm btn-outline-danger">Hapus</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-secondary">Belum ada relasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $relasi->links() }}</div>
            </div>
        </div>
    </div>
</div>
