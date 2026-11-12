<?php

namespace App\Livewire\Absensi;

use App\Models\Pertemuan;
use App\Services\AbsensiService;
use Livewire\Component;

class ScanQr extends Component
{
    public string $qrToken = '';

    public int $mahasiswaId;

    public string $message = '';

    public bool $success = false;

    public function mount(int $mahasiswaId): void
    {
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
