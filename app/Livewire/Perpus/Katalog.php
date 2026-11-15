<?php

namespace App\Livewire\Perpus;

use App\Models\PerpusBuku;
use App\Models\PerpusEksemplar;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * OPAC publik: katalog tanpa login (tanpa RequiresRoles — komponen publik).
 */
class Katalog extends Component
{
    use WithPagination;

    public string $cari = '';

    public function render()
    {
        return view('livewire.perpus.katalog', [
            'bukus' => PerpusBuku::withCount(['eksemplar as tersedia_count' => fn ($q) => $q->where('status', PerpusEksemplar::STATUS_TERSEDIA)])
                ->when($this->cari !== '', fn ($q) => $q->where(fn ($w) => $w->where('judul', 'like', '%'.$this->cari.'%')->orWhere('pengarang', 'like', '%'.$this->cari.'%')))
                ->orderBy('judul')
                ->paginate(12),
        ]);
    }
}
