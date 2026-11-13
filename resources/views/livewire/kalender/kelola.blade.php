<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($bisaKelola)
    <form wire:submit="simpan" class="card mb-3">
        <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} Agenda Kalender</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Judul</label><input type="text" wire:model="judul" class="form-control">@error('judul')<div class="text-danger">{{ $message }}</div>@enderror</div>
                <div class="col-md-3 mb-3"><label class="form-label">Jenis</label>
                    <select wire:model="jenis" class="form-select">
                        @foreach(['kuliah','uts','uas','libur','pembayaran','pmb','wisuda','krs','sidang','lain'] as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                    </select>
                    <div class="form-hint">Jenis <code>krs</code>, <code>pembayaran</code>, dan <code>sidang</code> mengunci layanan di luar rentangnya.</div>
                </div>
                <div class="col-md-3 mb-3"><label class="form-label">Tahun akademik</label>
                    <select wire:model="tahun_akademik_id" class="form-select"><option value="">—</option>
                        @foreach($tahuns as $t)<option value="{{ $t->id }}">{{ $t->tahun }} {{ $t->semester }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3"><label class="form-label">Tanggal mulai</label><input type="date" wire:model="tgl_mulai" class="form-control"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Tanggal selesai</label><input type="date" wire:model="tgl_selesai" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Deskripsi</label><input type="text" wire:model="deskripsi" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
    @endif
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Agenda</h3>
            <a href="{{ route('kalender.ics') }}" class="btn btn-sm btn-outline-primary">Export .ics</a>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Tanggal</th><th>Agenda</th><th>Jenis</th>@if($bisaKelola)<th class="text-end">Aksi</th>@endif</tr></thead>
                <tbody>
                    @forelse($events as $e)
                        <tr>
                            <td><span class="badge bg-blue-lt">{{ $e->tgl_mulai->format('d M Y') }}</span>@if($e->tgl_selesai)<span class="text-secondary small"> s/d {{ $e->tgl_selesai->format('d M Y') }}</span>@endif</td>
                            <td><strong>{{ $e->judul }}</strong>@if($e->deskripsi)<div class="text-secondary small">{{ $e->deskripsi }}</div>@endif</td>
                            <td><span class="badge bg-secondary">{{ $e->jenis }}</span></td>
                            @if($bisaKelola)
                            <td class="text-end">
                                <button wire:click="edit({{ $e->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                <button wire:click="hapus({{ $e->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus agenda?')">Hapus</button>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Belum ada agenda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $events->links() }}</div>
    </div>
</div>
