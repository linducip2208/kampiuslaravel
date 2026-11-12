<div>
    @if (session()->has('info'))
        <div>{{ session('info') }}</div>
    @endif

    <div>
        <input type="text" wire:model.live="search" placeholder="Cari nama / no. daftar / email">
        <select wire:model.live="filterStatus">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}">{{ $status }}</option>
            @endforeach
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Daftar</th>
                <th>Nama</th>
                <th>Gelombang</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendaftars as $pendaftar)
                <tr>
                    <td>{{ $pendaftar->no_daftar }}</td>
                    <td>{{ $pendaftar->nama }}<br><small>{{ $pendaftar->email }}</small></td>
                    <td>{{ $pendaftar->gelombang?->nama ?? '-' }}</td>
                    <td>{{ $pendaftar->status }}</td>
                    <td>
                        <button type="button" wire:click="pilih({{ $pendaftar->id }})">Detail</button>
                        <button type="button" wire:click="ubahStatus({{ $pendaftar->id }}, 'diverifikasi')">Verifikasi</button>
                        <button type="button" wire:click="ubahStatus({{ $pendaftar->id }}, 'lulus')">Lulus</button>
                        <button type="button" wire:click="ubahStatus({{ $pendaftar->id }}, 'tidak')">Tidak Lulus</button>
                        <button type="button" wire:click="daftarUlang({{ $pendaftar->id }})">Daftar Ulang</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada pendaftar.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $pendaftars->links() }}

    @if ($selectedId)
        <div>
            <h3>Verifikasi #{{ $selectedId }}</h3>
            <label for="catatan">Catatan verifikasi (wajib untuk penolakan)</label>
            <textarea id="catatan" wire:model="catatan"></textarea>
            @error('catatan') <span>{{ $message }}</span> @enderror
            @error('selectedId') <span>{{ $message }}</span> @enderror
            <div>
                <button type="button" wire:click="ubahStatus({{ $selectedId }}, 'diverifikasi')">Verifikasi</button>
                <button type="button" wire:click="ubahStatus({{ $selectedId }}, 'ditolak')">Tolak</button>
            </div>
        </div>
    @endif
</div>
