<div>
    @if(!empty($message))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    @if($kelasId)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Kuis Baru — {{ $kelas->matakuliah->kode ?? ('Kelas #'.$kelasId) }}</h3></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" wire:model="judul">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Durasi (mnt, 0=bebas)</label>
                        <input type="number" class="form-control" wire:model="durasi_menit" min="0" max="600">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Deadline</label>
                        <input type="datetime-local" class="form-control" wire:model="deadline">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mode</label>
                        <select class="form-select" wire:model="mode">
                            <option value="latihan">Latihan</option>
                            <option value="cbt">CBT</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Token CBT</label>
                        <input type="text" class="form-control" wire:model="token" maxlength="12" placeholder="opsional">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <label class="form-check"><input type="checkbox" class="form-check-input" wire:model="acak_soal"> Acak soal</label>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary" wire:click="simpan">Buat</button>
                    </div>
                    @error('judul')<div class="col-12 text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Impor dari Bank Soal</h3></div>
            <div class="card-body d-flex gap-2">
                <select class="form-select" wire:model="imporQuizId" style="max-width:320px">
                    <option value="">— pilih kuis —</option>
                    @foreach($daftar as $q)
                        <option value="{{ $q->id }}">{{ $q->judul }} ({{ $q->soal_count }} soal)</option>
                    @endforeach
                </select>
                <input type="number" class="form-control" wire:model="imporLimit" min="1" max="200" style="max-width:120px">
                <button class="btn btn-outline-primary" wire:click="impor">Impor</button>
            </div>
        </div>
    @else
        <div class="alert alert-info">Buka dengan <code>?kelas=ID</code> untuk mengelola kuis kelas.</div>
    @endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead><tr><th>Judul</th><th>Mode</th><th>Soal</th><th>Durasi</th><th>Deadline</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($daftar as $q)
                        <tr>
                            <td>{{ $q->judul }}</td>
                            <td>{{ $q->mode ?? 'latihan' }}</td>
                            <td>{{ $q->soal_count }}</td>
                            <td>{{ $q->durasi_menit }} mnt</td>
                            <td>{{ $q->deadline ?? '—' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" wire:click="$set('lihatQuizId', {{ $q->id }})">Hasil</button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="hapus({{ $q->id }})">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $daftar->links() }}</div>
    </div>
    @if($hasil)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Hasil Peserta</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>NIM</th><th>Nama</th><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach($hasil as $a)
                            <tr>
                                <td>{{ $a->mahasiswa->nim ?? '-' }}</td>
                                <td>{{ $a->mahasiswa->user->name ?? '-' }}</td>
                                <td>{{ $a->status }}</td>
                                <td>{{ $a->total_skor ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $hasil->links() }}</div>
        </div>
    @endif
    @if(!empty($esai) && count($esai) > 0)
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Koreksi Esai</h3></div>
            <div class="card-body">
                @foreach($esai as $j)
                    <div class="mb-3">
                        <div><strong>{{ $j->mahasiswa->user->name ?? ('Mhs #'.$j->mahasiswa_id) }}</strong>
                            <span class="text-secondary">soal #{{ $j->quiz_soal_id }} · bobot {{ $j->soal->bobot }}</span></div>
                        <div class="text-secondary">{{ $j->soal->pertanyaan }}</div>
                        <blockquote class="mt-1">{{ $j->jawaban }}</blockquote>
                        <div class="input-group input-group-sm" style="max-width:320px">
                            <input type="number" class="form-control" placeholder="skor" wire:model="koreksi.{{ $j->quiz_soal_id }}.{{ $j->mahasiswa_id }}" step="0.5" min="0">
                            <button class="btn btn-outline-success" wire:click="koreksi({{ $j->quiz_soal_id }}, {{ $j->mahasiswa_id }})">Nilai</button>
                        </div>
                        <div class="text-secondary small">Skor saat ini: {{ $j->skor ?? 'belum dinilai' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
