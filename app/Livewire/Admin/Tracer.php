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
            // Tren serapan per tahun lulus: responden vs terserap (bekerja/wirausaha).
            'tren' => TracerAlumni::selectRaw("tahun_lulus, COUNT(*) as responden, SUM(CASE WHEN status_kerja IN ('bekerja','wirausaha') THEN 1 ELSE 0 END) as terserap")
                ->groupBy('tahun_lulus')
                ->orderBy('tahun_lulus')
                ->get()
                ->map(fn ($r) => [
                    'tahun' => $r->tahun_lulus,
                    'responden' => (int) $r->responden,
                    'terserap' => (int) $r->terserap,
                    'persen' => $r->responden > 0 ? round($r->terserap / $r->responden * 100, 1) : 0.0,
                ])
                ->all(),
        ]);
    }
}
