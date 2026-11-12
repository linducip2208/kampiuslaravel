<?php

namespace App\Livewire\Absensi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Mahasiswa;
use App\Models\Pertemuan;
use App\Services\AbsensiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ScanQr extends Component
{
    use RequiresRoles;

    /** Otorisasi owner-scope dicek manual di mount(). */
    public array $allowedRoles = [];

    public string $qrToken = '';

    public int $mahasiswaId;

    public string $message = '';

    public bool $success = false;

    public function mount(int $mahasiswaId): void
    {
        // Mahasiswa hanya boleh absen untuk dirinya sendiri (anti-spoofing).
        $user = Auth::user();
        if ($user && $user->hasRole('mahasiswa') && ! $user->hasAnyRole(['superadmin', 'admin_prodi', 'dosen', 'keuangan', 'pimpinan'])) {
            $mahasiswaId = (int) Mahasiswa::where('user_id', $user->id)->value('id');
        }

        $this->mahasiswaId = $mahasiswaId;
    }

    public function scan(): void
    {
        $this->validate(['qrToken' => 'required|string|max:64']);

        $pertemuan = Pertemuan::where('qr_token', $this->qrToken)->first();
        if (! $pertemuan) {
            $this->success = false;
            $this->message = 'Token QR tidak valid.';

            return;
        }

        $result = AbsensiService::scan($pertemuan, $this->mahasiswaId, $this->qrToken);
        $this->success = $result['ok'];
        $this->message = $result['message'];
    }

    public function render()
    {
        return view('livewire.absensi.scan-qr');
    }
}
