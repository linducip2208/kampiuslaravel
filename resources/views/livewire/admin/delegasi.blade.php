<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Delegasi Peran Baru</h3></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">ID pemberi</label>
                    <input type="number" class="form-control" wire:model="dari_user_id" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email penerima</label>
                    <input type="email" class="form-control" wire:model="ke_email">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Peran</label>
                    <select class="form-select" wire:model="role">
                        @foreach($roles as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mulai</label>
                    <input type="date" class="form-control" wire:model="mulai_at">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" class="form-control" wire:model="sampai_at">
                </div>
                <div class="col-12">
                    <label class="form-label">Keperluan</label>
                    <input type="text" class="form-control" wire:model="keperluan">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" wire:click="simpan">Simpan</button>
                    @error('ke_email')<span class="text-danger">{{ $message }}</span>@enderror
                    @error('sampai_at')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Dari</th><th>Ke</th><th>Peran</th><th>Periode</th><th>Status</th><th>Diterapkan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($delegasis as $d)
                        <tr>
                            <td>{{ $d->dari->name ?? ('#'.$d->dari_user_id) }}</td>
                            <td>{{ $d->ke->name ?? ('#'.$d->ke_user_id) }}</td>
                            <td><span class="badge bg-blue-lt">{{ $d->role }}</span></td>
                            <td>{{ $d->mulai_at->format('Y-m-d') }} → {{ $d->sampai_at->format('Y-m-d') }}</td>
                            <td>{{ $d->status }}</td>
                            <td>{{ $d->diterapkan_pada ?? '—' }}</td>
                            <td>
                                @if($d->status === 'aktif')
                                    <button class="btn btn-sm btn-outline-danger" wire:click="nonaktifkan({{ $d->id }})">Nonaktifkan</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $delegasis->links() }}</div>
    </div>
</div>
