<?php

namespace App\Livewire\Riset;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\HibahPencairan;
use App\Models\HibahRiset;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-042: hibah riset + pencairan bertahap (total cair vs total dana).
 */
class Hibah extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public ?int $editId = null;

    public string $judul = '';

    public string $skema = '';

    public string $sumber_dana = '';

    public string $tahun = '';

    public int $total_dana = 0;

    public string $status = 'diajukan';

    public ?int $cairHibahId = null;

    public int $cairTahap = 1;

    public int $cairNominal = 0;

    public string $message = '';

    protected function authorizeRiset(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['dosen', 'superadmin', 'admin_prodi']), 403);
    }

    public function simpan(): void
    {
        $this->authorizeRiset();

        $this->validate([
            'judul' => 'required|string|max:255',
            'skema' => 'nullable|string|max:100',
            'sumber_dana' => 'nullable|string|max:100',
            'tahun' => 'required|string|max:9',
            'total_dana' => 'required|integer|min:0',
            'status' => 'required|in:'.implode(',', HibahRiset::STATUS),
        ]);

        $dosen = Dosen::where('user_id', Auth::id())->first();

        HibahRiset::updateOrCreate(['id' => $this->editId ?? 0], [
            'judul' => $this->judul,
            'skema' => $this->skema ?: null,
            'sumber_dana' => $this->sumber_dana ?: null,
            'tahun' => $this->tahun,
            'total_dana' => $this->total_dana,
            'ketua_dosen_id' => $dosen?->id,
            'status' => $this->status,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'hibah.disimpan',
            'model' => HibahRiset::class, 'model_id' => $this->editId ?? 0, 'created_at' => now(),
        ]);

        $this->reset(['editId', 'judul', 'skema', 'sumber_dana', 'tahun', 'total_dana', 'status']);
        $this->message = 'Hibah tersimpan.';
    }

    public function cair(): void
    {
        $this->authorizeRiset();

        $this->validate([
            'cairHibahId' => 'required|exists:hibah_riset,id',
            'cairTahap' => 'required|integer|min:1',
            'cairNominal' => 'required|integer|min:1',
        ]);

        HibahPencairan::create([
            'hibah_id' => $this->cairHibahId,
            'tahap' => $this->cairTahap,
            'nominal' => $this->cairNominal,
            'tanggal' => now()->toDateString(),
        ]);

        $this->reset(['cairHibahId', 'cairTahap', 'cairNominal']);
        $this->message = 'Pencairan dicatat.';
    }

    public function render()
    {
        return view('livewire.riset.hibah', [
            'hibahs' => HibahRiset::with('ketua.user')->latest('id')->paginate(10),
        ]);
    }
}
