<div>
    @if($message)
        <div class="alert alert-success" role="alert">{{ $message }}</div>
    @endif
    @error('alasan')<div class="alert alert-warning">{{ $message }}</div>@enderror
    @error('approval')<div class="alert alert-warning">{{ $message }}</div>@enderror

    @if(!$isAdmin)
        <form wire:submit="ajukan" class="card mb-3">
            <div class="card-header"><h3 class="card-title">Pengajuan Cuti Online</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Tahun akademik cuti</label>
                    <input type="number" wire:model="tahunAkademikId" class="form-control" placeholder="ID tahun akademik aktif">
                    @error('tahunAkademikId')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan cuti</label>
                    <textarea wire:model="alasan" class="form-control" rows="3" placeholder="Tulis alasan minimal 10 karakter"></textarea>
                    @error('alasan')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Ajukan Cuti</button>
            </div>
        </form>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Riwayat Cuti Saya</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Tahun Akademik</th><th>Status</th><th>Catatan Admin</th></tr></thead>
                    <tbody>
                        @forelse($riwayat as $cuti)
                            <tr>
                                <td>{{ $cuti->tahun_akademik_id ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $cuti->status }}</span></td>
                                <td>{{ $cuti->catatan_admin ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">Belum ada pengajuan cuti.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($isAdmin)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Antrean Persetujuan Cuti</h3>
                <div class="card-actions d-flex gap-2">
                    <input type="search" wire:model.live="search" class="form-control" placeholder="Cari NIM...">
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">Semua</option>
                        <option value="diajukan">Diajukan</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <label class="form-label">Catatan admin (opsional, dipakai saat setujui/tolak)</label>
                <input type="text" wire:model="catatan" class="form-control" placeholder="Catatan...">
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>NIM</th><th>Prodi</th><th>Alasan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($antrean as $cuti)
                            <tr>
                                <td>{{ $cuti->mahasiswa->nim ?? '-' }}</td>
                                <td>{{ $cuti->mahasiswa->prodi->nama ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($cuti->alasan, 60) }}</td>
                                <td><span class="badge bg-secondary">{{ $cuti->status }}</span></td>
                                <td class="text-end">
                                    @if($cuti->status === 'diajukan')
                                        <button wire:click="setujui({{ $cuti->id }})" class="btn btn-sm btn-success">Setujui</button>
                                        <button wire:click="tolak({{ $cuti->id }})" class="btn btn-sm btn-danger">Tolak</button>
                                    @else
                                        <span class="text-secondary">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary">Tidak ada pengajuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">{{ $antrean->links() }}</div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Kelola Status &amp; Pindah Prodi</h3></div>
            <div class="card-body">
                <p class="text-secondary">Isi ID mahasiswa, pilih status baru (aktif/cuti/nonaktif/do/keluar/lulus) atau prodi tujuan, lalu simpan.</p>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">ID Mahasiswa</label>
                        <input type="number" wire:model="mahasiswaKelolaId" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status baru</label>
                        <select wire:model="statusBaru" class="form-select">
                            <option value="aktif">aktif</option>
                            <option value="cuti">cuti</option>
                            <option value="nonaktif">nonaktif</option>
                            <option value="do">do</option>
                            <option value="keluar">keluar</option>
                            <option value="lulus">lulus</option>
                        </select>
                        @error('statusBaru')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ID Prodi tujuan</label>
                        <input type="number" wire:model="prodiTujuanId" class="form-control">
                        @error('prodiTujuanId')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button wire:click="ubahStatus({{ (int) $mahasiswaKelolaId }})" class="btn btn-primary">Ubah Status</button>
                        <button wire:click="pindahProdi({{ (int) $mahasiswaKelolaId }})" class="btn btn-outline-primary">Pindah Prodi</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
