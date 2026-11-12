<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="row row-cards">
        <div class="col-lg-5">
            <form wire:submit="buat" class="card mb-3">
                <div class="card-header"><h3 class="card-title">Buat Tiket Bantuan</h3></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Subjek</label><input type="text" wire:model="subjek" class="form-control">@error('subjek')<div class="text-danger">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Isi</label><textarea wire:model="isi" rows="4" class="form-control"></textarea>@error('isi')<div class="text-danger">{{ $message }}</div>@enderror</div>
                    <button type="submit" class="btn btn-primary">Kirim Tiket</button>
                </div>
            </form>
            @if($detail)
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $detail->subjek }}</h3></div>
                <div class="card-body">
                    <p>{{ $detail->isi }}</p>
                    <hr>
                    @foreach($detail->balasan as $b)
                        <div class="mb-2"><strong>{{ $b->user->name ?? 'Pengguna' }}:</strong> {{ $b->isi }}</div>
                    @endforeach
                    <form wire:submit="balas" class="mt-3">
                        <div class="mb-2"><textarea wire:model="balasan" rows="2" class="form-control" placeholder="Tulis balasan..."></textarea>@error('balasan')<div class="text-danger">{{ $message }}</div>@enderror</div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">Balas</button>
                            <button type="button" wire:click="tutup({{ $detail->id }})" class="btn btn-sm btn-outline-secondary">Tutup tiket</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Subjek</th><th>Pelapor</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($tikets as $t)
                                <tr>
                                    <td>{{ $t->subjek }}</td><td>{{ $t->user->name ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $t->status === 'selesai' ? 'success' : ($t->status === 'diproses' ? 'info' : 'warning') }}">{{ $t->status }}</span></td>
                                    <td class="text-end"><button wire:click="buka({{ $t->id }})" class="btn btn-sm btn-outline-primary">Buka</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-secondary">Belum ada tiket.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $tikets->links() }}</div>
            </div>
        </div>
    </div>
</div>
