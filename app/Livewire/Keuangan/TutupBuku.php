<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\TahunAkademik;
use App\Models\TutupBuku as TutupBukuModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-032: tutup-buku periode + buka-terkendali (dengan AuditLog).
 */
class TutupBuku extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'superadmin', 'admin_prodi'];

    public string $catatan = '';

    public function tutup(int $tahunId): void
    {
        $row = TutupBukuModel::firstOrNew(['tahun_akademik_id' => $tahunId]);
        $row->status = TutupBukuModel::STATUS_TUTUP;
        $row->ditutup_oleh = Auth::id();
        $row->ditutup_at = now();
        $row->dibuka_oleh = null;
        $row->dibuka_at = null;
        $row->catatan = $this->catatan ?: null;
        $row->save();

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'tutup-buku.ditutup',
            'model' => TutupBukuModel::class, 'model_id' => $row->id, 'created_at' => now(),
        ]);

        $this->reset('catatan');
        $this->dispatch('tutup-buku', status: 'ditutup');
    }

    public function buka(int $tahunId): void
    {
        $row = TutupBukuModel::firstOrNew(['tahun_akademik_id' => $tahunId]);
        $row->status = TutupBukuModel::STATUS_BUKA;
        $row->dibuka_oleh = Auth::id();
        $row->dibuka_at = now();
        $row->catatan = $this->catatan ?: null;
        $row->save();

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'tutup-buku.dibuka',
            'model' => TutupBukuModel::class, 'model_id' => $row->id, 'created_at' => now(),
        ]);

        $this->reset('catatan');
        $this->dispatch('tutup-buku', status: 'dibuka');
    }

    public function render()
    {
        $status = TutupBukuModel::pluck('status', 'tahun_akademik_id')->all();

        return view('livewire.keuangan.tutup-buku', [
            'periodes' => TahunAkademik::orderByDesc('id')->paginate(10),
            'status' => $status,
        ]);
    }
}
