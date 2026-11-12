<?php

namespace App\Livewire\Skripsi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Services\AkademikService;
use App\Services\KeuanganService;
use Livewire\Component;
use Livewire\WithPagination;

class Yudisium extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $search = '';

    public string $message = '';

    public function luluskan(int $mahasiswaId): void
    {
        $mhs = Mahasiswa::with('user')->findOrFail($mahasiswaId);

        if (KeuanganService::hasTunggakan($mhs->id)) {
            $this->addError('yudisium', "NIM {$mhs->nim} masih punya tunggakan.");

            return;
        }

        $ipk = AkademikService::ringkasan($mhs->id)['ipk'];

        if ($ipk < 2.0) {
            $this->addError('yudisium', "IPK NIM {$mhs->nim} ({$ipk}) di bawah 2.00.");

            return;
        }

        $sidangLulus = Sidang::where('status', 'lulus')
            ->whereIn('judul_id', SkripsiJudul::where('mahasiswa_id', $mhs->id)->pluck('id'))
            ->exists();

        if (! $sidangLulus) {
            $this->addError('yudisium', "NIM {$mhs->nim} belum lulus sidang.");

            return;
        }

        $mhs->update(['status' => 'lulus']);
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'mahasiswa.lulus',
            'model' => Mahasiswa::class,
            'model_id' => $mhs->id,
        ]);

        $this->message = "NIM {$mhs->nim} dinyatakan lulus.";
    }

    public function render()
    {
        $query = Mahasiswa::with(['user', 'prodi'])
            ->where('status', '!=', 'lulus')
            ->when($this->search !== '', fn ($q) => $q->where('nim', 'like', '%'.$this->search.'%'));

        return view('livewire.skripsi.yudisium', [
            'mahasiswa' => $query->latest('id')->paginate(10),
        ]);
    }
}
