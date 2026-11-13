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
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Kategori</label><input type="text" wire:model="kategori" class="form-control" placeholder="umum"></div>
                        <div class="col-6 mb-3"><label class="form-label">Prioritas</label>
                            <select wire:model="prioritas" class="form-select">
                                <option value="rendah">Rendah</option>
                                <option value="normal">Normal</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="kritis">Kritis</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Tiket</button>
                </div>
            </form>
            @if($detail)
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $detail->subjek }}</h3></div>
                <div class="card-body">
                    <p>{{ $detail->isi }}</p>
                    <p class="text-secondary small">Prioritas: {{ $detail->prioritas }} | Kategori: {{ $detail->kategori }} | Respon s.d: {{ $detail->due_respon_at?->format('d M Y H:i') ?? '-' }} | Resolusi s.d: {{ $detail->due_resolusi_at?->format('d M Y H:i') ?? '-' }} @if($detail->dieskalasi)<span class="badge bg-danger">Dieskalasi</span>@endif</p>
                    <hr>
                    @foreach($detail->balasan as $b)
                        <div class="mb-2"><strong>{{ $b->user->name ?? 'Pengguna' }}:</strong> {{ $b->isi }}</div>
                    @endforeach
                    <form wire:submit="balas" class="mt-3">
                        <div class="mb-2"><textarea wire:model="balasan" rows="2" class="form-control" placeholder="Tulis balasan..."></textarea>@error('balasan')<div class="text-danger">{{ $message }}</div>@enderror</div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">Balas</button>
                            <button type="button" wire:click="tutup({{ $detail->id }})" class="btn btn-sm btn-outline-secondary">Tutup tiket</button>
                            @if($isStaff && !$detail->dieskalasi)
                                <button type="button" wire:click="eskalasi({{ $detail->id }})" class="btn btn-sm btn-outline-danger">Eskalasi</button>
                            @endif
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
                        <thead><tr><th>Subjek</th><th>Pelapor</th><th>Prioritas</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($tikets as $t)
                                <tr>
                                    <td>{{ $t->subjek }}@if($t->dieskalasi) <span class="badge bg-danger">Eskalasi</span>@endif</td><td>{{ $t->user->name ?? '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $t->prioritas }}</span></td>
                                    <td><span class="badge bg-{{ $t->status === 'selesai' ? 'success' : ($t->status === 'diproses' ? 'info' : 'warning') }}">{{ $t->status }}</span></td>
                                    <td class="text-end"><button wire:click="buka({{ $t->id }})" class="btn btn-sm btn-outline-primary">Buka</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-secondary">Belum ada tiket.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $tikets->links() }}</div>
            </div>
        </div>
    </div>
</div>
