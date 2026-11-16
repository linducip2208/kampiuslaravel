<?php

namespace App\Livewire\Rekrutmen;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\LamaranKerja;
use App\Models\LowonganKerja;
use App\Models\OnboardingChecklist;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-039: kelola lowongan + seleksi pelamar (admin).
 */
class Kelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'hrd'];

    public ?int $editId = null;

    public string $judul = '';

    public string $unit = '';

    public string $jenis = 'tendik';

    public string $deskripsi = '';

    public int $kuota = 1;

    public string $tgl_tutup = '';

    public string $status = 'buka';

    public ?int $lihatLowonganId = null;

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'judul' => 'required|string|max:255',
            'unit' => 'nullable|string|max:100',
            'jenis' => 'required|in:'.implode(',', LowonganKerja::JENIS),
            'deskripsi' => 'nullable|string',
            'kuota' => 'required|integer|min:1',
            'tgl_tutup' => 'nullable|date|after:today',
            'status' => 'required|in:'.implode(',', LowonganKerja::STATUS),
        ]);

        LowonganKerja::updateOrCreate(['id' => $this->editId ?? 0], [
            'judul' => $this->judul,
            'unit' => $this->unit ?: null,
            'jenis' => $this->jenis,
            'deskripsi' => $this->deskripsi ?: null,
            'kuota' => $this->kuota,
            'tgl_tutup' => $this->tgl_tutup ?: null,
            'status' => $this->status,
        ]);

        $this->reset(['editId', 'judul', 'unit', 'jenis', 'deskripsi', 'kuota', 'tgl_tutup', 'status']);
        $this->message = 'Lowongan tersimpan.';
    }

    public function ubahStatusLamaran(int $id, string $status): void
    {
        abort_unless(in_array($status, LamaranKerja::STATUS, true), 422);

        $lamaran = LamaranKerja::findOrFail($id);
        $lamaran->update(['status' => $status]);

        // Lamaran diterima → buat checklist onboarding bawaan (idempoten).
        if ($status === 'diterima') {
            foreach (OnboardingChecklist::ITEM_DEFAULT as $item) {
                OnboardingChecklist::firstOrCreate(['lamaran_id' => $lamaran->id, 'item' => $item]);
            }
        }

        $this->message = "Lamaran #{$id} → {$status}.";
    }

    /** Centang/buka centang item onboarding. */
    public function toggleOnboarding(int $checklistId): void
    {
        $item = OnboardingChecklist::findOrFail($checklistId);
        $item->update(['selesai' => ! $item->selesai]);
    }

    public function render()
    {
        return view('livewire.rekrutmen.kelola', [
            'lowongans' => LowonganKerja::withCount('lamaran')->latest('id')->paginate(10),
            'pelamar' => $this->lihatLowonganId
                ? LamaranKerja::with('onboarding')->where('lowongan_id', $this->lihatLowonganId)->latest('id')->paginate(10)
                : null,
        ]);
    }
}
