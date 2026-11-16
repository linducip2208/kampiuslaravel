<?php

namespace App\Livewire\Aset;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Aset;
use App\Models\AsetOpname;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Ide bank: opname aset — catat kondisi fisik vs buku, terapkan ke data
 * aset, laporkan selisih (cocok/hilang/rusak).
 */
class Opname extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $asetId = null;

    public string $kondisiFisik = 'baik';

    public string $catatan = '';

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'asetId' => 'required|exists:aset,id',
            'kondisiFisik' => 'required|in:'.implode(',', AsetOpname::KONDISI),
            'catatan' => 'nullable|string|max:1000',
        ]);

        $aset = Aset::findOrFail($this->asetId);

        AsetOpname::create([
            'aset_id' => $aset->id,
            'kondisi_buku' => (string) $aset->kondisi,
            'kondisi_fisik' => $this->kondisiFisik,
            'catatan' => $this->catatan ?: null,
            'user_id' => Auth::id(),
        ]);

        if ($aset->kondisi !== $this->kondisiFisik) {
            $aset->update(['kondisi' => $this->kondisiFisik]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'aset.opname',
            'model' => Aset::class,
            'model_id' => $aset->id,
            'diff_json' => ['fisik' => $this->kondisiFisik],
            'created_at' => now(),
        ]);

        $this->reset(['asetId', 'kondisiFisik', 'catatan']);
        $this->kondisiFisik = 'baik';
        $this->message = "Opname {$aset->kode} tersimpan.";
    }

    public function render(): View
    {
        $riwayat = AsetOpname::with('aset')->latest('id')->paginate(10);

        return view('livewire.aset.opname', [
            'asets' => Aset::orderBy('kode')->take(200)->get(['id', 'kode', 'nama', 'kondisi', 'lokasi']),
            'riwayat' => $riwayat,
            'cocok' => (clone $riwayat->getCollection())->where(fn ($r) => $r->cocok())->count(),
        ]);
    }
}
