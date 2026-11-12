<div>
    @if($message)
        <div class="alert alert-success">{{ $message }}</div>
    @endif
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
            <button type="submit" class="btn btn-primary">Kirim Broadcast</button>
            <span class="text-secondary ms-2">Masuk inbox + FCM bila dikonfigurasi (halaman Integrasi).</span>
        </div>
    </form>
</div>
