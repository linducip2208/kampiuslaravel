<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    @if($kelasId)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Thread Baru</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" wire:model="judul">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Isi</label>
                        <textarea class="form-control" rows="3" wire:model="isi"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" wire:click="buatThread">Buat Thread</button>
                        @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">Buka dengan <code>?kelas=ID</code> untuk berdiskusi per kelas.</div>
    @endif
    @foreach($threads as $t)
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <strong>{{ $t->judul }}</strong>
                    @if($t->terkunci)<span class="badge bg-secondary">terkunci</span>@endif
                </div>
                <div class="text-secondary small">{{ $t->penulis->name ?? 'Anonim' }} · {{ $t->created_at }}</div>
                <p class="mt-2">{{ $t->isi }}</p>
                <button class="btn btn-sm btn-outline-primary" wire:click="$set('threadId', {{ $t->id }})">Lihat Balasan</button>
                @if(auth()->user()?->hasAnyRole(['dosen', 'superadmin', 'admin_prodi']))
                    @if($t->terkunci)
                        <button class="btn btn-sm btn-outline-success" wire:click="buka({{ $t->id }})">Buka</button>
                    @else
                        <button class="btn btn-sm btn-outline-danger" wire:click="kunci({{ $t->id }})">Kunci</button>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
    {{ $threads->links() }}
    @if(!empty($daftarBalasan))
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Balasan</h3></div>
            <div class="card-body">
                @foreach($daftarBalasan as $b)
                    <div class="mb-2"><strong>{{ $b->penulis->name ?? 'Anonim' }}</strong>
                        <span class="text-secondary small">{{ $b->created_at }}</span>
                        <button class="btn btn-sm btn-link p-0 ms-2" wire:click="kutip({{ $b->id }})">Kutip</button>
                        <div style="white-space: pre-wrap">{{ $b->isi }}</div>
                    </div>
                @endforeach
                {{ $daftarBalasan->links() }}
                <div class="mt-2">
                    <textarea class="form-control" rows="2" wire:model="balasan" placeholder="Tulis balasan..."></textarea>
                    <button class="btn btn-primary btn-sm mt-2" wire:click="balas">Kirim</button>
                    @error('balasan')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    @endif
</div>
