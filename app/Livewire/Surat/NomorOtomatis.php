<?php

namespace App\Livewire\Surat;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Surat as SuratModel;
use App\Services\SuratNomorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NomorOtomatis extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['keuangan', 'superadmin', 'admin_prodi'];

    public string $statusFilter = 'diajukan';

    public string $message = '';

    public function proses(int $id): void
    {
        $surat = SuratModel::findOrFail($id);
        SuratNomorService::assignIfEmpty($surat);
        $surat->update(['status' => 'diproses']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'surat.nomor.diterbitkan',
            'model' => SuratModel::class,
            'model_id' => $surat->id,
            'created_at' => now(),
        ]);

        $this->message = 'Nomor surat diterbitkan: '.$surat->fresh()->nomor_surat;
    }

    public function selesaikan(int $id): void
    {
        $surat = SuratModel::findOrFail($id);
        SuratNomorService::assignIfEmpty($surat);
        $surat->update(['status' => 'selesai']);

        $this->message = 'Surat selesai.';
    }

    public function tolak(int $id): void
    {
        SuratModel::where('id', $id)->update(['status' => 'ditolak']);
        $this->message = 'Surat ditolak.';
    }

    public function render()
    {
        return view('livewire.surat.nomor-otomatis', [
            'surats' => SuratModel::with('mahasiswa')
                ->when($this->statusFilter !== 'semua', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()->paginate(10),
        ]);
    }
}
