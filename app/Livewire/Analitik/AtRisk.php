<?php

namespace App\Livewire\Analitik;

use App\Livewire\Concerns\RequiresRoles;
use App\Services\Ai\AtRiskService;
use Livewire\Component;

class AtRisk extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'pimpinan', 'dosen'];

    /** @var list<array{mahasiswa_id: int, nim: string, nama: string, skor: float, level: string}> */
    public array $daftar = [];

    public function mount(AtRiskService $svc): void
    {
        $this->daftar = $svc->daftarBerisiko(20);
    }

    public function render()
    {
        return view('livewire.analitik.at-risk');
    }
}
