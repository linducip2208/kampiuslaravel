<div>
    <div class="mb-3">
        <input type="text" wire:model.live="search" class="form-control" placeholder="Cari NIM / nama mahasiswa bimbingan">
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>IPK (nilai)</th><th>KRS Terakhir</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($wali as $mhs)
                    @php
                        $warn = (int) ($bermasalah[$mhs->id] ?? 0);
                    @endphp
                    <tr class="{{ $warn > 0 ? 'table-warning' : '' }}">
                        <td>{{ $mhs->nim }}</td>
                        <td>{{ $mhs->nama ?? 'Mhs #'.$mhs->id }}</td>
                        <td>{{ $mhs->prodi_nama ?? '-' }}</td>
                        <td>{{ isset($ipk[$mhs->id]) ? number_format((float) $ipk[$mhs->id], 2) : '-' }}</td>
                        <td>{{ $krsStatus[$mhs->id] ?? '-' }}</td>
                        <td>
                            @if($warn > 0)
                                <span class="badge bg-warning text-dark">{{ $warn }} MK bermasalah (hadir &lt; 75% / D-E)</span>
                            @else
                                <span class="badge bg-success">Aman</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary">Belum ada mahasiswa bimbingan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $wali->links() }}
</div>
