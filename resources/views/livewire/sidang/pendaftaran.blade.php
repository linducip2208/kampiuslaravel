<div>
    @if ($message)<p>{{ $message }}</p>@endif
    @if ($error)<p>{{ $error }}</p>@endif
    <form wire:submit="save">
        <select wire:model="judul_id">
            <option value="">Pilih judul</option>
            @foreach ($juduls as $judul)
                <option value="{{ $judul->id }}">{{ $judul->judul }}</option>
            @endforeach
        </select>
        <select wire:model="jenis">
            <option value="proposal">proposal</option>
            <option value="hasil">hasil</option>
            <option value="tutup">tutup</option>
        </select>
        <input type="datetime-local" wire:model="tanggal">
        <select wire:model="ruang_id">
            <option value="">Pilih ruang</option>
            @foreach ($ruangs as $ruang)
                <option value="{{ $ruang->id }}">{{ $ruang->nama }}</option>
            @endforeach
        </select>
        <button type="submit">Daftar Sidang</button>
    </form>
    <ul>
        @foreach ($sidangs as $sidang)
            <li>{{ $sidang->jenis }} — {{ $sidang->tanggal }} — {{ $sidang->status }}</li>
        @endforeach
    </ul>
    {{ $sidangs->links() }}
</div>
