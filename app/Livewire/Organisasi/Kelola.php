<?php

namespace App\Livewire\Organisasi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Organisasi;
use App\Models\OrganisasiAnggota;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-040: kelola organisasi kemahasiswaan + keanggotaan per NIM.
 */
class Kelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $editId = null;

    public string $nama = '';

    public string $nomorSk = '';

    public string $jenis = 'ukm';

    public string $deskripsi = '';

    public ?int $pembina_dosen_id = null;

    public string $nimAnggota = '';

    public ?int $organisasiAnggotaId = null;

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'nama' => 'required|string|max:150',
            'nomorSk' => 'nullable|string|max:60',
            'jenis' => 'required|in:'.implode(',', Organisasi::JENIS),
            'deskripsi' => 'nullable|string|max:500',
            'pembina_dosen_id' => 'nullable|exists:dosen,id',
        ]);

        $org = Organisasi::updateOrCreate(['id' => $this->editId ?? 0], [
            'nama' => $this->nama,
            'nomor_sk' => $this->nomorSk ?: null,
            'jenis' => $this->jenis,
            'deskripsi' => $this->deskripsi ?: null,
            'pembina_dosen_id' => $this->pembina_dosen_id,
        ]);

        // Nomor SK dibuat otomatis bila kosong (format tahun + id).
        if (! $org->nomor_sk) {
            $org->update(['nomor_sk' => 'SK-ORG/'.date('Y').'/'.str_pad((string) $org->id, 4, '0', STR_PAD_LEFT)]);
        }

        $this->reset(['editId', 'nama', 'nomorSk', 'jenis', 'deskripsi', 'pembina_dosen_id']);
        $this->message = 'Organisasi tersimpan.';
    }

    public function tambahAnggota(): void
    {
        $this->validate([
            'organisasiAnggotaId' => 'required|exists:organisasi,id',
            'nimAnggota' => 'required|string',
        ]);

        $mhs = Mahasiswa::where('nim', trim($this->nimAnggota))->firstOrFail();

        OrganisasiAnggota::firstOrCreate(
            ['organisasi_id' => $this->organisasiAnggotaId, 'mahasiswa_id' => $mhs->id],
            ['peran' => 'anggota', 'status' => 'aktif']
        );

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'organisasi.anggota.ditambah',
            'model' => Organisasi::class, 'model_id' => $this->organisasiAnggotaId, 'created_at' => now(),
        ]);

        $this->reset(['nimAnggota']);
        $this->message = 'Anggota ditambahkan.';
    }

    public function lepasAnggota(int $id): void
    {
        OrganisasiAnggota::findOrFail($id)->delete();
        $this->message = 'Anggota dilepas.';
    }

    public function toggleAktif(int $id): void
    {
        $org = Organisasi::findOrFail($id);
        $org->update(['aktif' => ! $org->aktif]);
    }

    public function render()
    {
        return view('livewire.organisasi.kelola', [
            'organisasis' => Organisasi::with(['pembina.user'])->withCount('anggota')->latest('id')->paginate(10),
            'dosens' => Dosen::with('user')->orderBy('id')->take(100)->get(),
        ]);
    }
}
