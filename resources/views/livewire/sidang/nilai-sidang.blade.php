<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <form wire:submit.prevent="addPenguji">
        <select wire:model="sidang_id">
            <option value="">Pilih sidang</option>
            @foreach ($sidangs as $sidang)
                <option value="{{ $sidang->id }}">#{{ $sidang->id }} {{ $sidang->jenis }}</option>
            @endforeach
        </select>
        <select wire:model="dosen_id">
            <option value="">Pilih dosen</option>
            @foreach ($dosens as $dosen)
                <option value="{{ $dosen->id }}">{{ $dosen->nidn }}</option>
            @endforeach
        </select>
        <input type="text" wire:model="peran" placeholder="peran">
        <input type="number" step="0.01" wire:model="nilai" placeholder="nilai">
        <button type="submit">Simpan Nilai</button>
    </form>
    <form wire:submit.prevent="setStatus">
        <select wire:model="status">
            <option value="lulus">lulus</option>
            <option value="revisi">revisi</option>
            <option value="gagal">gagal</option>
            <option value="terdaftar">terdaftar</option>
        </select>
        <button type="submit">Update Status</button>
    </form>
    {{ $sidangs->links() }}
</div>
