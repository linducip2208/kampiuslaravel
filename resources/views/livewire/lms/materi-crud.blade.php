<div>
    <form wire:submit="save">
        <input type="text" wire:model="judul" placeholder="Judul materi">
        <input type="text" wire:model="link" placeholder="Link (opsional)">
        <textarea wire:model="deskripsi" placeholder="Deskripsi"></textarea>
        <input type="file" wire:model="file">
        <button type="submit">Simpan</button>
    </form>
    <ul>
        @foreach ($items as $item)
            <li>{{ $item->judul }}
                <button wire:click="edit({{ $item->id }})">Edit</button>
                <button wire:click="delete({{ $item->id }})">Hapus</button>
            </li>
        @endforeach
    </ul>
</div>
