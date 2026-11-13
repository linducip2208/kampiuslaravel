<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($error)
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
    @endif
    <form wire:submit="pinjam" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Ruang</label>
            <select wire:model="ruang_id" class="form-select">
                <option value="">— Pilih —</option>
                @foreach($ruangs as $r)<option value="{{ $r->id }}">{{ $r->nama }} ({{ $r->gedung }})</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tanggal</label>
            <input type="date" wire:model="tanggal" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Mulai</label>
            <input type="time" wire:model="jam_mulai" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Selesai</label>
            <input type="time" wire:model="jam_selesai" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Keperluan</label>
            <input type="text" wire:model="keperluan" class="form-control" placeholder="Rapat / UKM / ...">
        </div>
        <div class="col-12"><button type="submit" class="btn btn-primary">Ajukan Pinjam</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Ruang</th><th>Tanggal</th><th>Jam</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($daftar as $p)
                    <tr>
                        <td>{{ $p->ruang?->nama }}</td>
                        <td>{{ $p->tanggal?->format('Y-m-d') }}</td>
                        <td>{{ substr($p->jam_mulai, 0, 5) }}–{{ substr($p->jam_selesai, 0, 5) }}</td>
                        <td><span class="badge bg-secondary">{{ $p->status }}</span></td>
                        <td>
                            @if($bisaKelola && $p->status === 'diajukan')
                                <button wire:click="setujui({{ $p->id }})" class="btn btn-sm btn-success">Setujui</button>
                                <button wire:click="tolak({{ $p->id }})" class="btn btn-sm btn-danger">Tolak</button>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary">Belum ada pengajuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
