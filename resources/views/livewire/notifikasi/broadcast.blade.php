<div>
    @if($message)
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Template Broadcast</h3></div>
        <div class="card-body d-flex gap-2 flex-wrap align-items-end">
            <div class="flex-fill">
                <label class="form-label">Pilih template</label>
                <select wire:model="templateId" class="form-select">
                    <option value="">-- tanpa template --</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->nama }} ({{ $t->role_target }})</option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="pakaiTemplate" class="btn btn-outline-primary">Pakai template</button>
        </div>
    </div>
    <form wire:submit="send" class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Target role</label>
                <select wire:model="role" class="form-select">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="dosen">Dosen</option>
                    <option value="admin_prodi">Admin Prodi</option>
                    <option value="keuangan">Keuangan</option>
                    <option value="pimpinan">Pimpinan</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Judul</label>
                <input type="text" wire:model="judul" class="form-control">
                @error('judul')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Isi</label>
                <textarea wire:model="isi" class="form-control" rows="3"></textarea>
                @error('isi')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Simpan sebagai template (nama)</label>
                <div class="d-flex gap-2">
                    <input type="text" wire:model="templateNama" class="form-control" placeholder="cth: info-beasiswa">
                    <button type="button" wire:click="simpanTemplate" class="btn btn-outline-secondary">Simpan template</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
            <span class="text-secondary ms-2">Masuk inbox + FCM bila dikonfigurasi (halaman Integrasi). Variabel template: {nama}.</span>
        </div>
    </form>
    <form wire:submit="kirimWa" class="card mt-3">
        <div class="card-header"><h3 class="card-title">Broadcast WA Follow-up PMB (CRM)</h3></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Pesan WA khusus (opsional — kosongkan untuk memakai template follow-up otomatis)</label>
                <textarea wire:model="waMessage" class="form-control" rows="3" placeholder="cth: Halo, jangan lupa lunasi VA pendaftaran PMB Anda."></textarea>
                @error('waMessage')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-success">Kirim WA Follow-up</button>
            <span class="text-secondary ms-2">Terkirim ke pendaftar diajukan/diverifikasi yang belum difollow-up &gt; 3 hari. Gateway: halaman Integrasi (wa.gateway_url).</span>
        </div>
    </form>
</div>
