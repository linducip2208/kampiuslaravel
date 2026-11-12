<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <form wire:submit="generate">
        <select wire:model="tahun_id">
            <option value="">Pilih tahun akademik</option>
            @foreach ($tahuns as $tahun)
                <option value="{{ $tahun->id }}">{{ $tahun->tahun }} {{ $tahun->semester }}</option>
            @endforeach
        </select>
        <select wire:model="jenis">
            <option value="UKT">UKT</option>
            <option value="SPP">SPP</option>
            <option value="PMB">PMB</option>
            <option value="lain">lain</option>
        </select>
        <input type="number" wire:model="nominal" placeholder="Nominal">
        <select wire:model="prodi_id">
            <option value="">Semua prodi</option>
            @foreach ($prodis as $prodi)
                <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
            @endforeach
        </select>
        <input type="text" wire:model="angkatan" placeholder="Angkatan (opsional)">
        <button type="submit">Generate</button>
    </form>
</div>
