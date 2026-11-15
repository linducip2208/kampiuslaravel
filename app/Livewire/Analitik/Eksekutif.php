<?php

namespace App\Livewire\Analitik;

use App\Livewire\Concerns\RequiresRoles;
use App\Services\EksekutifService;
use Livewire\Component;

/**
 * M-056: dasbor eksekutif + KPI (pimpinan).
 */
class Eksekutif extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['pimpinan', 'rektor', 'warek', 'dekan', 'wadek', 'superadmin', 'admin_prodi'];

    public function render()
    {
        return view('livewire.analitik.eksekutif', ['kpi' => EksekutifService::kpi()]);
    }
}
