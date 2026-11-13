<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($error)
        <div class="alert alert-danger" role="alert">{{ $error }}</div>
    @endif
    <form wire:submit="generate" class="card mb-3">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tahun Akademik</label>
                    <select wire:model="tahun_akademik_id" class="form-select">
                        <option value="">— Pilih tahun —</option>
                        @foreach($tahuns as $t)
                            <option value="{{ $t->id }}">{{ $t->tahun }} ({{ $t->semester }})</option>
                        @endforeach
                    </select>
                    @error('tahun_akademik_id')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <div class="text-secondary">Ruang tersedia: {{ $ruangCount }}</div>
                    <button type="submit" class="btn btn-primary mt-1">Generate Otomatis Anti-Bentrok</button>
                </div>
            </div>
            @if($gagal !== [])
                <ul class="text-danger small mb-0">
                    @foreach($gagal as $g)<li>{{ $g }}</li>@endforeach
                </ul>
            @endif
        </div>
    </form>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Pinjam Ruang Online</h3></div>
        <div class="card-body">@livewire('jadwal.pinjam-ruang')</div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Presensi Dosen + BAP Otomatis</h3></div>
        <div class="card-body">@livewire('jadwal.presensi-dosen')</div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Kelas</th><th>Hari</th><th>Jam</th><th>Ruang</th><th>Dosen</th></tr></thead>
                <tbody>
                    @forelse($jadwal as $k)
                        <tr>
                            <td>{{ $k->nama }} — {{ $k->matakuliah?->nama }}</td>
                            <td>{{ $k->hari ?? '—' }}</td>
                            <td>{{ $k->jam_mulai ? substr($k->jam_mulai, 0, 5).'–'.substr($k->jam_selesai, 0, 5) : '—' }}</td>
                            <td>{{ $k->ruang?->nama ?? '—' }}</td>
                            <td>{{ $k->dosen?->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Belum ada jadwal. Pilih tahun lalu generate.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($jadwal, 'links'))
            <div class="card-footer">{{ $jadwal->links() }}</div>
        @endif
    </div>
</div>
