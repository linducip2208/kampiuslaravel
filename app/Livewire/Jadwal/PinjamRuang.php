<?php

namespace App\Livewire\Jadwal;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\PeminjamanRuang;
use App\Models\Ruang;
use App\Services\JadwalService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PinjamRuang extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    public ?int $ruang_id = null;

    public string $tanggal = '';

    public string $jam_mulai = '';

    public string $jam_selesai = '';

    public string $keperluan = '';

    public string $message = '';

    public string $error = '';

    public function pinjam(): void
    {
        $this->validate([
            'ruang_id' => 'required|exists:ruang,id',
            'tanggal' => 'required|date|after_or_equal:today',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i',
            'keperluan' => 'nullable|string|max:255',
        ]);

        try {
            JadwalService::pinjamRuang(
                (int) $this->ruang_id, (int) Auth::id(),
                $this->tanggal, $this->jam_mulai, $this->jam_selesai,
                $this->keperluan ?: null
            );
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
            $this->message = '';

            return;
        }

        $this->reset(['tanggal', 'jam_mulai', 'jam_selesai', 'keperluan']);
        $this->error = '';
        $this->message = 'Pengajuan pinjam ruang terkirim.';
    }

    public function setujui(int $id): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi']), 403);
        JadwalService::setujuiPinjam(PeminjamanRuang::findOrFail($id));
        $this->message = 'Peminjaman disetujui.';
    }

    public function tolak(int $id): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi']), 403);
        JadwalService::tolakPinjam(PeminjamanRuang::findOrFail($id));
        $this->message = 'Peminjaman ditolak.';
    }

    public function render()
    {
        return view('livewire.jadwal.pinjam-ruang', [
            'ruangs' => Ruang::orderBy('nama')->get(),
            'daftar' => PeminjamanRuang::with(['ruang', 'user'])->latest()->paginate(10),
            'bisaKelola' => (bool) Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi']),
        ]);
    }
}
