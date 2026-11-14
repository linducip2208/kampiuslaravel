<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="row row-cards">
        @forelse($lowongans as $l)
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="card-title">{{ $l->judul }}</h3>
                        <div class="text-secondary">{{ $l->unit }} · {{ strtoupper($l->jenis) }} · kuota {{ $l->kuota }}</div>
                        <p class="mt-2">{{ $l->deskripsi }}</p>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Nama lengkap</label>
                                <input type="text" class="form-control" wire:model="nama">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" wire:model="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">HP</label>
                                <input type="text" class="form-control" wire:model="hp">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" wire:click="kirim({{ $l->id }})">Lamar posisi ini</button>
                                @error('email')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">Belum ada lowongan dibuka.</div></div>
        @endforelse
    </div>
</div>
