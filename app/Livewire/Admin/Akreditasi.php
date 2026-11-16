<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AkreditasiDokumen;
use App\Models\Prodi;
use App\Models\SpmiSiklus;
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

    public ?int $siklusProdiId = null;

    public int $siklusTahun = 0;

    public string $siklusTahap = 'penetapan';

    public string $siklusStatus = 'sedang';

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

    /** Buka/tandai tahap siklus PPEMPMTI (idempoten per prodi+tahun+tahap). */
    public function simpanSiklus(): void
    {
        $this->validate([
            'siklusProdiId' => 'required|exists:prodi,id',
            'siklusTahun' => 'required|integer|min:2000|max:2100',
            'siklusTahap' => 'required|in:'.implode(',', SpmiSiklus::TAHAP),
            'siklusStatus' => 'required|in:'.implode(',', SpmiSiklus::STATUS),
        ]);

        SpmiSiklus::updateOrCreate(
            ['prodi_id' => $this->siklusProdiId, 'tahun' => $this->siklusTahun, 'tahap' => $this->siklusTahap],
            ['status' => $this->siklusStatus]
        );

        $this->reset(['siklusProdiId', 'siklusTahap', 'siklusStatus']);
        $this->message = 'Tahap siklus tersimpan.';
    }

    public function render()
    {
        return view('livewire.admin.akreditasi', [
            'prodis' => Prodi::orderBy('nama')->get(),
            'dokumens' => AkreditasiDokumen::with('prodi')->latest('id')->paginate(10),
            'siklus' => SpmiSiklus::with('prodi')->orderByDesc('tahun')->orderBy('prodi_id')->get(),
        ]);
    }
}
