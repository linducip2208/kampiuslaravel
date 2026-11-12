<div>
    <form wire:submit="save">
        <input type="text" wire:model="judul" placeholder="Judul tugas">
        <textarea wire:model="deskripsi" placeholder="Deskripsi"></textarea>
        <input type="datetime-local" wire:model="deadline">
        <button type="submit">Simpan</button>
    </form>
    <ul>
        @foreach ($items as $item)
            <li>{{ $item->judul }} — {{ $item->deadline }}
                <button wire:click="edit({{ $item->id }})">Edit</button>
                <button wire:click="delete({{ $item->id }})">Hapus</button>
            </li>
        @endforeach
    </ul>
    {{ $items->links() }}
</div>
