<div>
    <form wire:submit="save" class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Mahasiswa</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Hadir</th></tr></thead>
                <tbody>
                    @foreach($rows as $i => $row)
                        <tr>
                            <td>Mhs #{{ $row['mahasiswa_id'] }}</td>
                            <td><input type="number" wire:model="rows.{{ $i }}.tugas" min="0" max="100" class="form-control"></td>
                            <td><input type="number" wire:model="rows.{{ $i }}.uts" min="0" max="100" class="form-control"></td>
                            <td><input type="number" wire:model="rows.{{ $i }}.uas" min="0" max="100" class="form-control"></td>
                            <td><input type="number" wire:model="rows.{{ $i }}.kehadiran" min="0" max="100" class="form-control"></td>
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
