<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Pembayaran;
use App\Services\KeuanganService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerifikasiBayar extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['keuangan', 'superadmin'];

    public string $statusFilter = 'pending';

    public string $message = '';

    public function verify(int $id): void
    {
        KeuanganService::verifyPembayaran($id, (int) Auth::id(), true);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pembayaran.verified',
            'model' => Pembayaran::class,
            'model_id' => $id,
            'created_at' => now(),
        ]);

        $this->message = 'Pembayaran diverifikasi.';
    }

    public function reject(int $id): void
    {
        KeuanganService::verifyPembayaran($id, (int) Auth::id(), false);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pembayaran.rejected',
            'model' => Pembayaran::class,
            'model_id' => $id,
            'created_at' => now(),
        ]);

        $this->message = 'Pembayaran ditolak.';
    }

    public function render()
    {
        return view('livewire.keuangan.verifikasi-bayar', [
            'pembayarans' => Pembayaran::with(['tagihan.mahasiswa'])
                ->when($this->statusFilter !== 'semua', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()->paginate(10),
        ]);
    }
}
