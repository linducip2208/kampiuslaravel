<div>
    <div class="row row-cards">
        <div class="col-lg-6">
            <form wire:submit="simpanProfil" class="card">
                <div class="card-header"><h3 class="card-title">Profil Saya</h3></div>
                <div class="card-body">
                    @if($messageProfil)
                        <div class="alert alert-success" role="alert">{{ $messageProfil }}</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" wire:model="name" class="form-control">
                        @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" wire:model="email" class="form-control">
                        @error('email')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Profil</button>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form wire:submit="gantiPassword" class="card">
                <div class="card-header"><h3 class="card-title">Ganti Password</h3></div>
                <div class="card-body">
                    @if($messagePassword)
                        <div class="alert alert-success" role="alert">{{ $messagePassword }}</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Password saat ini</label>
                        <input type="password" wire:model="passwordSaatIni" class="form-control" autocomplete="current-password">
                        @error('passwordSaatIni')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password baru (min. 8 karakter)</label>
                        <input type="password" wire:model="passwordBaru" class="form-control" autocomplete="new-password">
                        @error('passwordBaru')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi password baru</label>
                        <input type="password" wire:model="passwordBaruConfirmation" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-warning">Ganti Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
