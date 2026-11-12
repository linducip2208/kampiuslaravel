<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <select wire:model.live="statusFilter">
        <option value="diajukan">diajukan</option>
        <option value="diproses">diproses</option>
        <option value="selesai">selesai</option>
        <option value="ditolak">ditolak</option>
        <option value="semua">semua</option>
    </select>
    <ul>
        @foreach ($surats as $surat)
            <li>#{{ $surat->id }} {{ $surat->jenis }} — {{ $surat->nomor_surat ?? '-' }} — {{ $surat->status }}
                <button wire:click="proses({{ $surat->id }})">Terbitkan Nomor</button>
                <button wire:click="selesaikan({{ $surat->id }})">Selesai</button>
                <button wire:click="tolak({{ $surat->id }})">Tolak</button>
            </li>
        @endforeach
    </ul>
    {{ $surats->links() }}
</div>
