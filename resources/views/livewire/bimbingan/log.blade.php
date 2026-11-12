<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <form wire:submit="save">
        <select wire:model="judul_id">
            <option value="">Pilih judul</option>
            @foreach ($juduls as $judul)
                <option value="{{ $judul->id }}">{{ $judul->judul }}</option>
            @endforeach
        </select>
        <textarea wire:model="catatan" placeholder="Catatan bimbingan"></textarea>
        <input type="date" wire:model="tanggal">
        <button type="submit">Simpan Log</button>
    </form>
    <ul>
        @foreach ($logs as $log)
            <li>{{ $log->catatan }} — {{ $log->status }}
                <button wire:click="setujui({{ $log->id }})">Setujui</button>
                <button wire:click="revisi({{ $log->id }})">Revisi</button>
            </li>
        @endforeach
    </ul>
    {{ $logs->links() }}
</div>
