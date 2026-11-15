<?php

namespace App\Livewire\Riset;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Dosen;
use App\Models\Publikasi as PublikasiModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-043: publikasi & HKI — dosen kelola miliknya; staf melihat semua.
 */
class Publikasi extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public ?int $editId = null;

    public string $judul = '';

    public string $jenis = 'jurnal';

    public string $penulis = '';

    public string $penerbit = '';

    public string $tahun = '';

    public string $doi = '';

    public string $status = 'draft';

    public string $message = '';

    protected function authorizeRiset(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi']), 403);
    }

    protected function dosenId(): ?int
    {
        return Dosen::where('user_id', Auth::id())->value('id');
    }

    public function simpan(): void
    {
        $this->authorizeRiset();
        abort_unless($this->dosenId() || Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi']), 403, 'Akun dosen tidak ditemukan.');

        $this->validate([
            'judul' => 'required|string|max:255',
            'jenis' => 'required|in:'.implode(',', PublikasiModel::JENIS),
            'penulis' => 'nullable|string|max:500',
            'penerbit' => 'nullable|string|max:150',
            'tahun' => 'required|string|max:9',
            'doi' => 'nullable|string|max:150',
            'status' => 'required|in:'.implode(',', PublikasiModel::STATUS),
        ]);

        if ($this->editId) {
            $row = PublikasiModel::findOrFail($this->editId);
            abort_unless(
                Auth::user()->hasAnyRole(['superadmin', 'admin_prodi']) || $row->dosen_id === $this->dosenId(),
                403
            );
            $row->update([
                'judul' => $this->judul, 'jenis' => $this->jenis,
                'penulis' => $this->penulis ?: null, 'penerbit' => $this->penerbit ?: null,
                'tahun' => $this->tahun, 'doi' => $this->doi ?: null, 'status' => $this->status,
            ]);
        } else {
            PublikasiModel::create([
                'judul' => $this->judul, 'jenis' => $this->jenis,
                'penulis' => $this->penulis ?: null, 'penerbit' => $this->penerbit ?: null,
                'tahun' => $this->tahun, 'doi' => $this->doi ?: null, 'status' => $this->status,
                'dosen_id' => $this->dosenId(),
            ]);
        }

        $this->reset(['editId', 'judul', 'jenis', 'penulis', 'penerbit', 'tahun', 'doi', 'status']);
        $this->message = 'Publikasi tersimpan.';
    }

    public function hapus(int $id): void
    {
        $row = PublikasiModel::findOrFail($id);
        abort_unless(
            Auth::user()->hasAnyRole(['superadmin', 'admin_prodi']) || $row->dosen_id === $this->dosenId(),
            403
        );
        $row->delete();
        $this->message = 'Publikasi dihapus.';
    }

    public function render()
    {
        return view('livewire.riset.publikasi', [
            'items' => PublikasiModel::with('dosen.user')->latest('id')->paginate(10),
        ]);
    }
}
