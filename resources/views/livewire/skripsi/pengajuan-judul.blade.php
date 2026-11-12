<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <form wire:submit="save">
        <input type="text" wire:model="judul" placeholder="Judul skripsi">
        @error('judul')<span>{{ $message }}</span>@enderror
        <textarea wire:model="deskripsi" placeholder="Deskripsi"></textarea>
        <button type="submit">Ajukan</button>
    </form>
    <ul>
        @foreach ($riwayat as $item)
            <li>{{ $item->judul }} — {{ $item->status }}</li>
        @endforeach
    </ul>
</div>
