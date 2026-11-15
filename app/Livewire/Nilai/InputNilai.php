<?php

namespace App\Livewire\Nilai;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Nilai;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InputNilai extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    public int $kelasId;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array{tugas:float,uts:float,uas:float,kehadiran:float} */
    public array $bobot = ['tugas' => 30, 'uts' => 30, 'uas' => 30, 'kehadiran' => 10];

    /** @var array<int, float|null> */
    public array $remedial = [];

    protected function rules(): array
    {
        return [
            'rows.*.mahasiswa_id' => 'required|integer',
            'rows.*.tugas' => 'required|numeric|min:0|max:100',
            'rows.*.uts' => 'required|numeric|min:0|max:100',
            'rows.*.uas' => 'required|numeric|min:0|max:100',
            'rows.*.kehadiran' => 'required|numeric|min:0|max:100',
            'bobot.tugas' => 'required|numeric|min:0|max:100',
            'bobot.uts' => 'required|numeric|min:0|max:100',
            'bobot.uas' => 'required|numeric|min:0|max:100',
            'bobot.kehadiran' => 'required|numeric|min:0|max:100',
        ];
    }

    public function mount(int $kelasId): void
    {
        $this->authorizeKelas($kelasId);
        $this->kelasId = $kelasId;
        $this->rows = Nilai::where('kelas_id', $kelasId)->get()
            ->map(fn (Nilai $nilai) => [
                'mahasiswa_id' => $nilai->mahasiswa_id,
                'tugas' => (float) $nilai->tugas,
                'uts' => (float) $nilai->uts,
                'uas' => (float) $nilai->uas,
                'kehadiran' => (float) $nilai->kehadiran,
            ])->toArray();
    }

    public function addRow(int $mahasiswaId): void
    {
        $this->rows[] = ['mahasiswa_id' => $mahasiswaId, 'tugas' => 0, 'uts' => 0, 'uas' => 0, 'kehadiran' => 0];
    }

    /**
     * Daftar mahasiswa dengan kehadiran < 75% (warning).
     *
     * @return list<array{mahasiswa_id:int,kehadiran:float}>
     */
    public function warningKehadiran(): array
    {
        $out = [];
        foreach ($this->rows as $row) {
            $hadir = (float) ($row['kehadiran'] ?? 0);
            if (Nilai::isKehadiranKurang($hadir)) {
                $out[] = ['mahasiswa_id' => (int) $row['mahasiswa_id'], 'kehadiran' => $hadir];
            }
        }

        return $out;
    }

    public function save(): void
    {
        $this->validate();
        Nilai::normalisasiBobot($this->bobot);
        $this->authorizeKelas($this->kelasId);

        $lockedIds = Nilai::where('kelas_id', $this->kelasId)->where('is_locked', true)->pluck('mahasiswa_id')->flip();

        DB::transaction(function () use ($lockedIds): void {
            foreach ($this->rows as $row) {
                if (isset($lockedIds[$row['mahasiswa_id']])) {
                    continue;
                }

                $na = Nilai::hitungNa((float) $row['tugas'], (float) $row['uts'], (float) $row['uas'], (float) $row['kehadiran'], $this->bobot);
                $huruf = Nilai::konversiHuruf($na);

                Nilai::updateOrCreate(
                    ['mahasiswa_id' => $row['mahasiswa_id'], 'kelas_id' => $this->kelasId],
                    [
                        'tugas' => $row['tugas'],
                        'uts' => $row['uts'],
                        'uas' => $row['uas'],
                        'kehadiran' => $row['kehadiran'],
                        'na' => $na,
                        'huruf' => $huruf,
                        'bobot' => Nilai::bobotHuruf($huruf),
                    ]
                );
            }
        });

        $peringatan = count($this->warningKehadiran());
        session()->flash('success', $peringatan > 0
            ? "Nilai tersimpan. Perhatian: {$peringatan} mahasiswa kehadiran < 75%."
            : 'Nilai tersimpan.');
    }

    /**
     * Remedial: hanya untuk huruf D/E, NA dibatasi maksimal 55 (C).
     */
    public function simpanRemedial(int $mahasiswaId): void
    {
        $this->validate(['remedial.'.$mahasiswaId => 'required|numeric|min:0|max:100']);
        Nilai::normalisasiBobot($this->bobot);
        $this->authorizeKelas($this->kelasId);

        $nilai = Nilai::where('mahasiswa_id', $mahasiswaId)->where('kelas_id', $this->kelasId)->firstOrFail();

        if ($nilai->is_locked) {
            session()->flash('error', 'Nilai terkunci, remedial ditolak.');

            return;
        }

        if (! $nilai->terapkanRemedial((float) $this->remedial[$mahasiswaId], $this->bobot)) {
            session()->flash('error', 'Remedial hanya untuk huruf D/E.');

            return;
        }

        $nilai->save();
        unset($this->remedial[$mahasiswaId]);
        session()->flash('success', "Remedial Mhs #{$mahasiswaId} tersimpan (maks C).");
    }

    public function render()
    {
        return view('livewire.nilai.input-nilai', [
            'peringatan' => $this->warningKehadiran(),
        ]);
    }

    private function authorizeKelas(int $kelasId): void
    {
        $user = Auth::user();
        if (! $user || $user->hasAnyRole(['superadmin', 'admin_prodi'])) {
            return;
        }
        if ($user->hasRole('dosen')) {
            $dosenId = Dosen::where('user_id', $user->id)->value('id');
            $ownerId = Kelas::where('id', $kelasId)->value('dosen_id');
            abort_unless($dosenId && $ownerId && (int) $dosenId === (int) $ownerId, 403);
        }
    }
}
