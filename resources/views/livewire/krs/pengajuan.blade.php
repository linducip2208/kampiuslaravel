<div>
    @if (session()->has('info'))
        <div>{{ session('info') }}</div>
    @endif

    @if ($pesanBlokir)
        <div>{{ $pesanBlokir }}</div>
    @endif

    <div>
        <label for="ipk">IPK semester lalu</label>
        <input id="ipk" type="number" step="0.01" min="0" max="4" wire:model="ipk">
        @error('ipk') <span>{{ $message }}</span> @enderror
        <p>Total SKS dipilih: <strong>{{ $totalSks }}</strong> / maksimal <strong>{{ $sksMaks }}</strong> SKS</p>
    </div>

    <form wire:submit="ajukan">
        <table>
            <thead>
                <tr>
                    <th>Pilih</th>
                    <th>Kelas</th>
                    <th>Matakuliah</th>
                    <th>SKS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kelasTersedia as $kelas)
                    <tr>
                        <td><input type="checkbox" wire:click="toggleKelas({{ $kelas->id }})" @checked(in_array($kelas->id, $kelasDipilih))></td>
                        <td>{{ $kelas->nama ?? ('Kelas #'.$kelas->id) }}</td>
                        <td>{{ $kelas->matakuliah_nama ?? '-' }} ({{ $kelas->matakuliah_kode ?? '-' }})</td>
                        <td>{{ $kelas->sks ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada kelas tersedia untuk tahun akademik ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        @error('kelasDipilih') <span>{{ $message }}</span> @enderror

        <button type="submit">Ajukan KRS</button>
    </form>

    @if (isset($waitlistSaya) && $waitlistSaya->count() > 0)
        <h3>Waiting list saya</h3>
        <ul>
            @foreach ($waitlistSaya as $wl)
                <li>
                    Kelas #{{ $wl->kelas_id }} — {{ $wl->status }}
                    @if ($wl->status === 'menunggu')
                        <button type="button" wire:click="keluarWaitlist({{ $wl->kelas_id }})">Keluar</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
