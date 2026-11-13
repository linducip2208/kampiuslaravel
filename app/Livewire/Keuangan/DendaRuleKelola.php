<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\DendaRule;
use App\Models\TahunAkademik;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kelola aturan denda keterlambatan tagihan (dipakai applyDendaOtomatis).
 */
class DendaRuleKelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'superadmin', 'admin_prodi'];

    public ?int $editId = null;

    public ?int $tahun_akademik_id = null;

    public string $jenis = 'UKT';

    public int $terlambat_hari_min = 1;

    public string $mode = 'persen';

    public int $nilai = 0;

    public ?int $maksimal = null;

    public bool $aktif = true;

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'tahun_akademik_id' => 'nullable|exists:tahun_akademik,id',
            'jenis' => 'required|in:UKT,SPP,PMB,lain',
            'terlambat_hari_min' => 'required|integer|min:1|max:365',
            'mode' => 'required|in:persen,nominal',
            'nilai' => 'required|integer|min:1|max:100000000',
            'maksimal' => 'nullable|integer|min:0|max:1000000000',
            'aktif' => 'boolean',
        ]);

        if ($this->mode === 'persen' && $this->nilai > 100) {
            $this->addError('nilai', 'Mode persen maksimal 100%.');

            return;
        }

        DendaRule::updateOrCreate(['id' => $this->editId ?? 0], [
            'tahun_akademik_id' => $this->tahun_akademik_id,
            'jenis' => $this->jenis,
            'terlambat_hari_min' => $this->terlambat_hari_min,
            'mode' => $this->mode,
            'nilai' => $this->nilai,
            'maksimal' => $this->maksimal,
            'aktif' => $this->aktif,
        ]);

        $this->reset(['editId', 'tahun_akademik_id', 'jenis', 'terlambat_hari_min', 'mode', 'nilai', 'maksimal', 'aktif']);
        $this->message = 'Aturan denda tersimpan.';
    }

    public function edit(int $id): void
    {
        $rule = DendaRule::findOrFail($id);
        $this->editId = $rule->id;
        $this->tahun_akademik_id = $rule->tahun_akademik_id;
        $this->jenis = $rule->jenis;
        $this->terlambat_hari_min = (int) $rule->terlambat_hari_min;
        $this->mode = $rule->mode;
        $this->nilai = (int) $rule->nilai;
        $this->maksimal = $rule->maksimal !== null ? (int) $rule->maksimal : null;
        $this->aktif = (bool) $rule->aktif;
    }

    public function batal(): void
    {
        $this->reset(['editId', 'tahun_akademik_id', 'jenis', 'terlambat_hari_min', 'mode', 'nilai', 'maksimal', 'aktif']);
    }

    public function hapus(int $id): void
    {
        DendaRule::findOrFail($id)->delete();
        $this->message = 'Aturan denda dihapus.';
    }

    public function render()
    {
        return view('livewire.keuangan.denda-rule-kelola', [
            'rules' => DendaRule::with('tahunAkademik')->latest('id')->paginate(10),
            'tahuns' => TahunAkademik::orderByDesc('id')->get(),
        ]);
    }
}
