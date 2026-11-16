<div>
    <div class="card mb-3">
        <div class="card-body">
            <strong>Ringkasan status kerja:</strong>
            @forelse($ringkasan as $st => $total)
                <span class="badge bg-secondary me-1">{{ $st }}: {{ $total }}</span>
            @empty
                <span class="text-secondary">Belum ada data tracer.</span>
            @endforelse
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Tren serapan per tahun lulus</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead><tr><th>Tahun</th><th>Responden</th><th>Terserap</th><th style="min-width:220px">%</th></tr></thead>
                <tbody>
                    @forelse($tren as $t)
                        <tr>
                            <td>{{ $t['tahun'] }}</td>
                            <td>{{ $t['responden'] }}</td>
                            <td>{{ $t['terserap'] }}</td>
                            <td>
                                <div class="progress" style="min-width:160px">
                                    <div class="progress-bar" style="width: {{ min(100, $t['persen']) }}%">{{ $t['persen'] }}%</div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Belum ada data tren.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tracer Study</h3></div>
        <div class="card-body">
            <select class="form-select" wire:model.live="filterStatus" style="max-width:240px">
                <option value="">Semua status</option>
                <option value="bekerja">Bekerja</option>
                <option value="wirausaha">Wirausaha</option>
                <option value="kuliah_lanjut">Kuliah lanjut</option>
                <option value="mencari_kerja">Mencari kerja</option>
                <option value="lainnya">Lainnya</option>
            </select>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>NIM</th><th>Nama</th><th>Status</th><th>Perusahaan</th><th>Jabatan</th><th>Gaji</th></tr></thead>
                <tbody>
                    @foreach($tracers as $t)
                        <tr>
                            <td>{{ $t->mahasiswa->nim ?? '-' }}</td>
                            <td>{{ $t->mahasiswa->user->name ?? '-' }}</td>
                            <td>{{ $t->status_kerja }}</td>
                            <td>{{ $t->perusahaan ?? '-' }}</td>
                            <td>{{ $t->jabatan ?? '-' }}</td>
                            <td>{{ $t->rentang_gaji ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $tracers->links() }}</div>
    </div>
</div>
