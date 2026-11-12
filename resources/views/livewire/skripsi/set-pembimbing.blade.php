<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <table>
        @foreach ($pengajuans as $item)
            <tr>
                <td>{{ $item->judul }}</td>
                <td>{{ $item->status }}</td>
                <td>
                    <button wire:click="$set('judulId', {{ $item->id }})">Pilih</button>
                </td>
            </tr>
        @endforeach
    </table>
    {{ $pengajuans->links() }}
    @if ($judulId)
        <form wire:submit.prevent="setPembimbing({{ $judulId }})">
            <select wire:model="pembimbing1_id">
                <option value="">Pembimbing 1</option>
                @foreach ($dosens as $dosen)
                    <option value="{{ $dosen->id }}">{{ $dosen->nidn }}</option>
                @endforeach
            </select>
            <select wire:model="pembimbing2_id">
                <option value="">Pembimbing 2 (opsional)</option>
                @foreach ($dosens as $dosen)
                    <option value="{{ $dosen->id }}">{{ $dosen->nidn }}</option>
                @endforeach
            </select>
            <select wire:model="status">
                <option value="disetujui">disetujui</option>
                <option value="ditolak">ditolak</option>
                <option value="revisi">revisi</option>
            </select>
            <button type="submit">Simpan</button>
        </form>
    @endif
</div>
