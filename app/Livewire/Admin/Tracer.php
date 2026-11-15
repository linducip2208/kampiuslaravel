<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\TracerAlumni;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-055: rekap tracer study (admin) + ringkasan status kerja.
 */
class Tracer extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'pimpinan', 'rektor', 'warek', 'dekan', 'wadek'];

    public string $filterStatus = '';

    public function render()
    {
        return view('livewire.admin.tracer', [
            'tracers' => TracerAlumni::with('mahasiswa.user')
                ->when($this->filterStatus !== '', fn ($q) => $q->where('status_kerja', $this->filterStatus))
                ->latest('id')
                ->paginate(10),
            'ringkasan' => TracerAlumni::selectRaw('status_kerja, COUNT(*) as total')->groupBy('status_kerja')->pluck('total', 'status_kerja')->all(),
        ]);
    }
}
