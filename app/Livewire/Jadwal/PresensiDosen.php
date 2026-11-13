<?php

namespace App\Livewire\Jadwal;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Pertemuan;
use App\Services\JadwalService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PresensiDosen extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    public ?int $pertemuan_id = null;

    public string $status_hadir = 'hadir';

    public string $realisasi = '';

    public string $kendala = '';

    public string $message = '';

    public string $error = '';

    public function simpan(): void
    {
        $this->validate([
            'pertemuan_id' => 'required|exists:pertemuan,id',
            'status_hadir' => 'required|in:hadir,izin,sakit,alfa',
            'realisasi' => 'nullable|string|max:2000',
            'kendala' => 'nullable|string|max:2000',
        ]);

        abort_unless(Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi', 'dosen']), 403);

        try {
            JadwalService::presensiDosen(
                Pertemuan::findOrFail($this->pertemuan_id),
                $this->status_hadir,
                ['realisasi' => $this->realisasi ?: null, 'kendala' => $this->kendala ?: null]
            );
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
            $this->message = '';

            return;
        }

        $this->error = '';
        $this->message = 'Presensi dosen + BAP tersimpan.';
    }

    public function render()
    {
        $pertemuan = $this->pertemuan_id ? Pertemuan::with(['kelas.matakuliah', 'kelas.dosen.user'])->find($this->pertemuan_id) : null;

        return view('livewire.jadwal.presensi-dosen', [
            'daftar' => Pertemuan::with(['kelas.matakuliah'])->latest('id')->take(30)->get(),
            'bap' => $pertemuan ? JadwalService::buatBap($pertemuan) : null,
        ]);
    }
}
