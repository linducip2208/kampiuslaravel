<div>
    @if(session()->has('message') || !empty($message ?? ''))
        <div class="alert alert-success">{{ $message ?? session('message') }}</div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tutup Buku Periode</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Periode</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($periodes as $p)
                        <tr>
                            <td>{{ $p->tahun }} · {{ ucfirst($p->semester) }}</td>
                            <td><span class="badge bg-{{ ($status[$p->id] ?? 'buka') === 'tutup' ? 'red' : 'green' }}">{{ $status[$p->id] ?? 'buka' }}</span></td>
                            <td>
                                @if(($status[$p->id] ?? 'buka') === 'tutup')
                                    <button class="btn btn-sm btn-outline-success" wire:click="buka({{ $p->id }})">Buka kembali</button>
                                @else
                                    <button class="btn btn-sm btn-outline-danger" wire:click="tutup({{ $p->id }})">Tutup</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <label class="form-label">Catatan (opsional)</label>
            <input type="text" class="form-control" wire:model="catatan" placeholder="Berita acara penutupan">
        </div>
        <div class="card-footer">{{ $periodes->links() }}</div>
    </div>
</div>
