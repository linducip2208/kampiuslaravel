<div>
    <form wire:submit="save">
        @foreach ($rows as $i => $row)
            <div>
                <span>Mhs #{{ $row['mahasiswa_id'] }}</span>
                <input type="number" wire:model="rows.{{ $i }}.tugas" min="0" max="100">
                <input type="number" wire:model="rows.{{ $i }}.uts" min="0" max="100">
                <input type="number" wire:model="rows.{{ $i }}.uas" min="0" max="100">
                <input type="number" wire:model="rows.{{ $i }}.kehadiran" min="0" max="100">
            </div>
        @endforeach
        <button type="submit">Simpan Nilai</button>
    </form>
</div>
