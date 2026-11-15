<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    <div class="card kampius-card">
        <div class="card-header"><h3 class="card-title">Impor Bank Soal (CSV)</h3></div>
        <div class="card-body">
            <p class="text-secondary">Format tiap baris: <code>pertanyaan,jenis,opsi,kunci,bobot</code> (delimiter <code>,</code> atau <code>;</code> otomatis). Opsi dipisah <code>|</code> (2–6 untuk PG). Jenis <code>pg</code> wajib kunci A–F, jenis <code>esai</code> tanpa kunci. Bobot 1–100 (kosong = 1). Baris header dilewati.</p>
            <form wire:submit="impor">
                <div class="mb-3">
                    <label class="form-label" for="bsi-file">File CSV (maks 2 MB)</label>
                    <input id="bsi-file" type="file" wire:model="fileCsv" accept=".csv,.txt" class="form-control @error('fileCsv') is-invalid @enderror">
                    @error('fileCsv')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('matakuliahId')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="impor,fileCsv">
                    <span wire:loading.remove wire:target="impor">Impor</span>
                    <span wire:loading wire:target="impor"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Mengimpor…</span>
                </button>
            </form>
        </div>
    </div>

    @if($message)
        <div class="card kampius-card mt-3">
            <div class="card-header"><h3 class="card-title">Hasil Impor</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead><tr><th>Masuk</th><th>Gagal</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-success-lt">{{ $hasil['masuk'] }}</span></td>
                            <td><span class="badge bg-danger-lt">{{ $hasil['gagal'] }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if(count($hasil['errors']) > 0)
                <div class="card-body">
                    <h4>Daftar error per baris</h4>
                    <ul class="mb-0">
                        @foreach($hasil['errors'] as $error)
                            <li class="text-danger small">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
