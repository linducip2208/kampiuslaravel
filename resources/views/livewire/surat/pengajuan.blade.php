<div>
    @if ($message)<p>{{ $message }}</p>@endif
    <form wire:submit="save">
        <select wire:model="jenis">
            <option value="aktif_kuliah">aktif_kuliah</option>
            <option value="pengantar_kp">pengantar_kp</option>
            <option value="keterangan_lulus">keterangan_lulus</option>
            <option value="legalisir">legalisir</option>
        </select>
        <textarea wire:model="keperluan" placeholder="Keperluan"></textarea>
        <button type="submit">Ajukan</button>
    </form>
    <ul>
        @foreach ($riwayat as $surat)
            <li>{{ $surat->jenis }} — {{ $surat->nomor_surat ?? '-' }} — {{ $surat->status }}</li>
        @endforeach
    </ul>
</div>
