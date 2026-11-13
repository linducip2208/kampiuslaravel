<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @if($gagalBaris)
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-title">Sebagian baris gagal</h4>
            <ul class="mb-0">@foreach($gagalBaris as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Jenis data</label>
                    <select wire:model.live="jenis" class="form-select">
                        <option value="mahasiswa">Mahasiswa (nim;nama;email;hp;prodi_kode;angkatan)</option>
                        <option value="matakuliah">Matakuliah (kode;nama;prodi_kode;sks;semester)</option>
                        <option value="dosen">Dosen (nidn;nama;email;prodi_kode)</option>
                        <option value="krs">KRS (nim;tahun;semester;kelas_id)</option>
                        <option value="nilai">Nilai (nim;kelas_id;tugas;uts;uas;kehadiran)</option>
                        <option value="tagihan">Tagihan (nim;tahun;semester;jenis;nominal)</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">File CSV (delimiter ;)</label>
                    <input type="file" wire:model="file" class="form-control" accept=".csv,.txt">
                    @error('file')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                    <button wire:click="impor" class="btn btn-primary">Impor</button>
                    <a href="{{ route('admin.impor.template', ['jenis' => $jenis]) }}" class="btn btn-outline-secondary">Template</a>
                </div>
            </div>
        </div>
    </div>
</div>
