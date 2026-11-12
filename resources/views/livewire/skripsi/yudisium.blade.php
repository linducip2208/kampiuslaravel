<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @error('yudisium')<div class="alert alert-warning">{{ $message }}</div>@enderror
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Yudisium Kelulusan</h3>
            <div class="card-actions"><input type="search" wire:model.live="search" class="form-control" placeholder="Cari NIM..."></div>
        </div>
        <div class="card-body">
            <p class="text-secondary">Syarat otomatis dicek: sidang lulus, bebas tunggakan, IPK ≥ 2.00.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($mahasiswa as $m)
                        <tr>
                            <td>{{ $m->nim }}</td><td>{{ $m->user->name ?? '-' }}</td>
                            <td>{{ $m->prodi->nama ?? '-' }}</td><td><span class="badge bg-secondary">{{ $m->status }}</span></td>
                            <td class="text-end"><button wire:click="luluskan({{ $m->id }})" class="btn btn-sm btn-success" onclick="return confirm('Luluskan NIM {{ $m->nim }}?')">Luluskan</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Tidak ada kandidat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $mahasiswa->links() }}</div>
    </div>
</div>
