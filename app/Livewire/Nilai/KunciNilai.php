<?php

namespace App\Livewire\Nilai;

use App\Models\Nilai;
use Livewire\Component;

class KunciNilai extends Component
{
    public int $kelasId;

    public function mount(int $kelasId): void
    {
        $this->kelasId = $kelasId;
    }

    public function kunci(): void
    {
        Nilai::where('kelas_id', $this->kelasId)->update(['is_locked' => true]);
        session()->flash('success', 'Nilai dikunci.');
    }

    public function buka(): void
    {
        Nilai::where('kelas_id', $this->kelasId)->update(['is_locked' => false]);
        session()->flash('success', 'Kunci nilai dibuka (revisi admin).');
    }

    public function render()
    {
        return view('livewire.nilai.kunci-nilai', [
            'total' => Nilai::where('kelas_id', $this->kelasId)->count(),
            'locked' => Nilai::where('kelas_id', $this->kelasId)->where('is_locked', true)->count(),
        ]);
    }
}
