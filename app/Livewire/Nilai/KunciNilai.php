<?php

namespace App\Livewire\Nilai;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Kelas;
use App\Models\Nilai;
use Livewire\Component;

class KunciNilai extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'superadmin', 'admin_prodi'];

    public int $kelasId;

    public ?int $tahunAkademikId = null;

    public function mount(int $kelasId, ?int $tahunAkademikId = null): void
    {
        $this->kelasId = $kelasId;
        $this->tahunAkademikId = $tahunAkademikId ?? Kelas::where('id', $kelasId)->value('tahun_akademik_id');
    }

    public function kunci(): void
    {
        $kurang = Nilai::where('kelas_id', $this->kelasId)
            ->where('kehadiran', '<', Nilai::KEHADIRAN_MINIMAL)
            ->count();

        Nilai::where('kelas_id', $this->kelasId)->update(['is_locked' => true]);

        session()->flash('success', $kurang > 0
            ? "Nilai dikunci. Perhatian: {$kurang} mahasiswa kehadiran < 75%."
            : 'Nilai dikunci.');
    }

    public function buka(): void
    {
        Nilai::where('kelas_id', $this->kelasId)->update(['is_locked' => false]);
        session()->flash('success', 'Kunci nilai dibuka (revisi admin).');
    }

    /**
     * Rekap EDOM per dosen pengampu kelas-kelas terkait.
     */
    public function edomRekap(): array
    {
        $dosenId = Kelas::where('id', $this->kelasId)->value('dosen_id');

        if (! $dosenId) {
            return ['dosen_id' => null, 'avg_skor' => 0.0, 'total_responden' => 0, 'total_kelas' => 0, 'per_kelas' => []];
        }

        return Nilai::rekapEdomDosen((int) $dosenId, $this->tahunAkademikId);
    }

    public function render()
    {
        return view('livewire.nilai.kunci-nilai', [
            'total' => Nilai::where('kelas_id', $this->kelasId)->count(),
            'locked' => Nilai::where('kelas_id', $this->kelasId)->where('is_locked', true)->count(),
            'peringatan' => Nilai::where('kelas_id', $this->kelasId)
                ->where('kehadiran', '<', Nilai::KEHADIRAN_MINIMAL)->count(),
            'edom' => $this->edomRekap(),
        ]);
    }
}
