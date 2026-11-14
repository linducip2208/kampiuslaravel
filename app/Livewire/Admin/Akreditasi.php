<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AkreditasiDokumen;
use App\Models\Prodi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-050: bukti akreditasi per prodi + verifikasi.
 */
class Akreditasi extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $editId = null;

    public ?int $prodi_id = null;

    public string $kategori = '';

    public string $judul = '';

    public string $tautan = '';

    public string $status = 'draft';

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'prodi_id' => 'required|exists:prodi,id',
            'kategori' => 'required|string|max:60',
            'judul' => 'required|string|max:255',
            'tautan' => 'nullable|url|max:500',
            'status' => 'required|in:'.implode(',', AkreditasiDokumen::STATUS),
        ]);

        AkreditasiDokumen::updateOrCreate(['id' => $this->editId ?? 0], [
            'prodi_id' => $this->prodi_id,
            'kategori' => $this->kategori,
            'judul' => $this->judul,
            'tautan' => $this->tautan ?: null,
            'status' => $this->status,
        ]);

        $this->reset(['editId', 'prodi_id', 'kategori', 'judul', 'tautan', 'status']);
        $this->message = 'Dokumen tersimpan.';
    }

    public function verifikasi(int $id): void
    {
        $dok = AkreditasiDokumen::findOrFail($id);
        $dok->update([
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => Auth::id(),
            'diverifikasi_at' => now(),
        ]);
        $this->message = 'Dokumen terverifikasi.';
    }

    public function hapus(int $id): void
    {
        AkreditasiDokumen::findOrFail($id)->delete();
        $this->message = 'Dokumen dihapus.';
    }

    public function render()
    {
        return view('livewire.admin.akreditasi', [
            'prodis' => Prodi::orderBy('nama')->get(),
            'dokumens' => AkreditasiDokumen::with('prodi')->latest('id')->paginate(10),
        ]);
    }
}
