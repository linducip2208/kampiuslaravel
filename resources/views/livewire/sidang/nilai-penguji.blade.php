<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Nilai Sidang Saya (Penguji)</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Peran</th><th>Nilai Saya</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($tugas as $t)
                        <tr>
                            <td>{{ $t->sidang->judul->mahasiswa->user->name ?? ('Mhs #'.$t->sidang->judul->mahasiswa_id ?? '-') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($t->sidang->judul->judul ?? '-', 60) }}</td>
                            <td>{{ $t->peran }}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm" wire:model="nilai.{{ $t->id }}" min="0" max="100" step="0.5" style="max-width:120px" placeholder="{{ $t->nilai ?? '-' }}">
                                @error('nilai.'.$t->id)<div class="text-danger small">{{ $message }}</div>@enderror
                            </td>
                            <td><button class="btn btn-sm btn-primary" wire:click="simpan({{ $t->id }})">Simpan</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $tugas->links() }}</div>
    </div>
</div>
