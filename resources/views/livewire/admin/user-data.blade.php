<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @error('form.hapus')<div class="alert alert-warning">{{ $message }}</div>@enderror
    <div class="row row-cards">
        <div class="col-lg-4">
            <form wire:submit="simpan" class="card">
                <div class="card-header"><h3 class="card-title">{{ $editId ? 'Ubah' : 'Tambah' }} User</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" wire:model="form.name" class="form-control">
                        @error('form.name')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" wire:model="form.email" class="form-control">
                        @error('form.email')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password {{ $editId ? '(kosongkan bila tidak diubah)' : '' }}</label>
                        <input type="password" wire:model="form.password" class="form-control" autocomplete="new-password">
                        @error('form.password')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select wire:model="form.role" class="form-select">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('form.role')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        @if($editId)
                            <button type="button" wire:click="batal" class="btn btn-outline-secondary">Batal</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar User</h3>
                    <div class="card-actions d-flex gap-2">
                        <select wire:model.live="roleFilter" class="form-select">
                            <option value="">Semua role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <input type="search" wire:model.live="search" class="form-control" placeholder="Cari nama/email...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->email }}</td>
                                    <td>{{ $row->roles->pluck('name')->join(', ') }}</td>
                                    <td class="text-end">
                                        <button wire:click="edit({{ $row->id }})" class="btn btn-sm btn-outline-primary">Ubah</button>
                                        <button wire:click="resetPassword({{ $row->id }})" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reset password user ini ke Password123?')">Reset</button>
                                        <button wire:click="hapus({{ $row->id }})" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus user ini?')">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-secondary">Belum ada user.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</div>
