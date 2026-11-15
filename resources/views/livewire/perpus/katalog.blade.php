<div>
    <div class="card mb-3">
        <div class="card-body d-flex gap-2">
            <input type="search" class="form-control form-control-lg" wire:model.live.debounce.500ms="cari" placeholder="Cari judul atau pengarang...">
        </div>
    </div>
    <div class="row g-3">
        @forelse($bukus as $b)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="subheader">{{ $b->kode }}{{ $b->kategori ? ' · '.$b->kategori : '' }}</div>
                        <h3 class="card-title mb-1">{{ $b->judul }}</h3>
                        <div class="text-secondary small">{{ $b->pengarang ?? 'Pengarang tidak dicantumkan' }}{{ $b->penerbit ? ' · '.$b->penerbit : '' }}{{ $b->tahun ? ' ('.$b->tahun.')' : '' }}</div>
                        <div class="mt-2">
                            @if($b->tersedia_count > 0)
                                <span class="badge bg-green-lt">{{ $b->tersedia_count }} tersedia</span>
                            @else
                                <span class="badge bg-secondary-lt">habis dipinjam</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">Belum ada koleksi yang cocok.</div></div>
        @endforelse
    </div>
    <div class="d-flex justify-content-center mt-3">{{ $bukus->links() }}</div>
</div>
