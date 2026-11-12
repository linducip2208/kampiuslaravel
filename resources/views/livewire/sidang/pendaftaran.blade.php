<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($error)
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
    @endif
    <form wire:submit="save" class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Judul</label>
                    <select wire:model="judul_id" class="form-select">
                        <option value="">— Pilih judul —</option>
                        @foreach($juduls as $judul)
                            <option value="{{ $judul->id }}">{{ $judul->judul }}</option>
                        @endforeach
                    </select>
                    @error('judul_id')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jenis</label>
                    <select wire:model="jenis" class="form-select">
                        <option value="proposal">proposal</option>
                        <option value="hasil">hasil</option>
                        <option value="tutup">tutup</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="datetime-local" wire:model="tanggal" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ruang</label>
                    <select wire:model="ruang_id" class="form-select">
                        <option value="">— Pilih ruang —</option>
                        @foreach($ruangs as $ruang)
                            <option value="{{ $ruang->id }}">{{ $ruang->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Daftar Sidang</button>
        </div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Jenis</th><th>Tanggal</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($sidangs as $sidang)
                        <tr><td>{{ $sidang->jenis }}</td><td>{{ $sidang->tanggal }}</td><td><span class="badge bg-secondary">{{ $sidang->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Belum ada pendaftaran sidang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($sidangs, 'links'))
            <div class="card-footer">{{ $sidangs->links() }}</div>
        @endif
    </div>
</div>
