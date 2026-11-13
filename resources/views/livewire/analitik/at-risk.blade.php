<div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Mahasiswa At-Risk (prediktor dini)</h3></div>
        <div class="card-body table-responsive">
            @if(empty($daftar))
                <div class="alert alert-success">Tidak ada mahasiswa berisiko saat ini.</div>
            @else
                <table class="table table-bordered">
                    <thead><tr><th>NIM</th><th>Nama</th><th>Skor</th><th>Level</th></tr></thead>
                    <tbody>
                    @foreach($daftar as $d)
                        <tr>
                            <td>{{ $d['nim'] }}</td>
                            <td>{{ $d['nama'] }}</td>
                            <td>{{ $d['skor'] }}</td>
                            <td><span class="badge {{ $d['level'] === 'tinggi' ? 'bg-danger' : 'bg-warning' }}">{{ $d['level'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
