<div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Mahasiswa At-Risk (prediktor dini)</h3></div>
        <div class="card-body">
            @if($pesan !== '')<div class="alert alert-info">{{ $pesan }}</div>@endif
            <div class="table-responsive">
            @if(empty($daftar))
                <div class="alert alert-success">Tidak ada mahasiswa berisiko saat ini.</div>
            @else
                <table class="table table-bordered">
                    <thead><tr><th>NIM</th><th>Nama</th><th>Skor</th><th>Level</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach($daftar as $d)
                        <tr>
                            <td>{{ $d['nim'] }}</td>
                            <td>{{ $d['nama'] }}</td>
                            <td>{{ $d['skor'] }}</td>
                            <td><span class="badge {{ $d['level'] === 'tinggi' ? 'bg-danger' : 'bg-warning' }}">{{ $d['level'] }}</span></td>
                            <td>
                                @if($d['level'] === 'tinggi')
                                    <button class="btn btn-sm btn-outline-primary" wire:click="notifikasiPa({{ $d['mahasiswa_id'] }})">Kirim ke PA</button>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
