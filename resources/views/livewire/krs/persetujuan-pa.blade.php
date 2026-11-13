<div>
    @if (session()->has('info'))
        <div>{{ session('info') }}</div>
    @endif

    <div>
        <input type="text" wire:model.live="search" placeholder="Cari berdasarkan ID mahasiswa">
    </div>

    <div>
        <label for="catatan">Alasan penolakan</label>
        <input id="catatan" type="text" wire:model="catatan">
        @error('catatan') <span>{{ $message }}</span> @enderror
    </div>

    <table>
        <thead>
            <tr>
                <th>ID KRS</th>
                <th>Mahasiswa ID</th>
                <th>Total SKS</th>
                <th>Detail</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($krsList as $krs)
                <tr>
                    <td>{{ $krs->id }}</td>
                    <td>{{ $krs->mahasiswa_id }}</td>
                    <td>{{ $krs->total_sks }}</td>
                    <td>{{ $krs->details->count() }} kelas</td>
                    <td>
                        <button type="button" wire:click="setujui({{ $krs->id }})" wire:confirm="Setujui KRS #{{ $krs->id }}?" wire:loading.attr="disabled" wire:target="setujui" class="btn btn-sm btn-success">Setujui</button>
                        <button type="button" wire:click="tolak({{ $krs->id }})" wire:confirm="Tolak KRS #{{ $krs->id }}? Pastikan alasan penolakan sudah diisi." wire:loading.attr="disabled" wire:target="tolak" class="btn btn-sm btn-outline-danger">Tolak</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada KRS menunggu persetujuan.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $krsList->links() }}
</div>
