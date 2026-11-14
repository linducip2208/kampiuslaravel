<?php

namespace App\Livewire\Riset;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Dosen;
use App\Models\Pengabdian as PengabdianModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-044: pengabdian masyarakat — dosen kelola miliknya; staf melihat semua.
 */
class Pengabdian extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public ?int $editId = null;

    public string $judul = '';

    public string $lokasi = '';

    public string $tanggal_mulai = '';

    public string $tanggal_selesai = '';

    public int $dana = 0;

    public string $status = 'rencana';

    public string $laporan_ringkas = '';

    public string $message = '';

    protected function authorizeRiset(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['dosen', 'superadmin', 'admin_prodi']), 403);
    }

    protected function dosenId(): ?int
    {
        return Dosen::where('user_id', Auth::id())->value('id');
    }

    public function simpan(): void
    {
        $this->authorizeRiset();

        $this->validate([
            'judul' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:150',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'dana' => 'required|integer|min:0',
            'status' => 'required|in:'.implode(',', PengabdianModel::STATUS),
            'laporan_ringkas' => 'nullable|string|max:1000',
        ]);

        $data = [
            'judul' => $this->judul,
            'lokasi' => $this->lokasi ?: null,
            'tanggal_mulai' => $this->tanggal_mulai ?: null,
            'tanggal_selesai' => $this->tanggal_selesai ?: null,
            'dana' => $this->dana,
            'status' => $this->status,
            'laporan_ringkas' => $this->laporan_ringkas ?: null,
        ];

        if ($this->editId) {
            $row = PengabdianModel::findOrFail($this->editId);
            abort_unless(
                Auth::user()->hasAnyRole(['superadmin', 'admin_prodi']) || $row->ketua_dosen_id === $this->dosenId(),
                403
            );
            $row->update($data);
        } else {
            $data['ketua_dosen_id'] = $this->dosenId();
            PengabdianModel::create($data);
        }

        $this->reset(['editId', 'judul', 'lokasi', 'tanggal_mulai', 'tanggal_selesai', 'dana', 'status', 'laporan_ringkas']);
        $this->message = 'Kegiatan pengabdian tersimpan.';
    }

    public function hapus(int $id): void
    {
        $row = PengabdianModel::findOrFail($id);
        abort_unless(
            Auth::user()->hasAnyRole(['superadmin', 'admin_prodi']) || $row->ketua_dosen_id === $this->dosenId(),
            403
        );
        $row->delete();
        $this->message = 'Kegiatan dihapus.';
    }

    public function render()
    {
        return view('livewire.riset.pengabdian', [
            'items' => PengabdianModel::with('ketua.user')->latest('id')->paginate(10),
        ]);
    }
}
