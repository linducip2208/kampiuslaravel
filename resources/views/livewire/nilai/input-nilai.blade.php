<div>
    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Bobot Tugas (%)</label>
                    <input type="number" wire:model="bobot.tugas" min="0" max="100" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bobot UTS (%)</label>
                    <input type="number" wire:model="bobot.uts" min="0" max="100" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bobot UAS (%)</label>
                    <input type="number" wire:model="bobot.uas" min="0" max="100" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bobot Kehadiran (%)</label>
                    <input type="number" wire:model="bobot.kehadiran" min="0" max="100" class="form-control">
                </div>
            </div>
            <div class="form-text">Total bobot harus 100. Default 30 / 30 / 30 / 10.</div>
        </div>
    </div>

    @if(count($peringatan) > 0)
        <div class="alert alert-warning">
            <strong>Warning kehadiran &lt; 75%:</strong>
            {{ collect($peringatan)->map(fn ($w) => 'Mhs #'.$w['mahasiswa_id'].' ('.$w['kehadiran'].'%)')->join(', ') }}
        </div>
    @endif

    <form wire:submit="save" class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Mahasiswa</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Hadir (%)</th><th>Remedial UAS</th><th></th></tr></thead>
                <tbody>
                    @foreach($rows as $i => $row)
                        <tr class="{{ ((float) ($row['kehadiran'] ?? 0)) < 75 ? 'table-warning' : '' }}">
                            <td>Mhs #{{ $row['mahasiswa_id'] }}</td>
                            <td><input type="number" wire:model="rows.{{ $i }}.tugas" min="0" max="100" class="form-control"></td>
                            <td><input type="number" wire:model="rows.{{ $i }}.uts" min="0" max="100" class="form-control"></td>
                            <td><input type="number" wire:model="rows.{{ $i }}.uas" min="0" max="100" class="form-control"></td>
                            <td><input type="number" wire:model="rows.{{ $i }}.kehadiran" min="0" max="100" class="form-control"></td>
                            <td>
                                <input type="number" wire:model="remedial.{{ $row['mahasiswa_id'] }}" min="0" max="100" class="form-control" placeholder="0-100">
                            </td>
                            <td>
                                <button type="button" wire:click="simpanRemedial({{ $row['mahasiswa_id'] }})" class="btn btn-sm btn-outline-primary">Remedial</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Simpan Nilai</button>
        </div>
    </form>
</div>
