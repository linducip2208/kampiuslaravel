<?php

namespace App\Livewire\Nilai;

use App\Models\Nilai;
use Livewire\Component;

class InputNilai extends Component
{
    public int $kelasId;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    protected function rules(): array
    {
        return [
            'rows.*.mahasiswa_id' => 'required|integer',
            'rows.*.tugas' => 'required|numeric|min:0|max:100',
            'rows.*.uts' => 'required|numeric|min:0|max:100',
            'rows.*.uas' => 'required|numeric|min:0|max:100',
            'rows.*.kehadiran' => 'required|numeric|min:0|max:100',
        ];
    }

    public function mount(int $kelasId): void
    {
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

    public function save(): void
    {
        $this->validate();

        foreach ($this->rows as $row) {
            $locked = Nilai::where('mahasiswa_id', $row['mahasiswa_id'])
                ->where('kelas_id', $this->kelasId)->value('is_locked');
            if ($locked) {
                continue;
            }

            $na = Nilai::hitungNa((float) $row['tugas'], (float) $row['uts'], (float) $row['uas'], (float) $row['kehadiran']);
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

        session()->flash('success', 'Nilai tersimpan.');
    }

    public function render()
    {
        return view('livewire.nilai.input-nilai');
    }
}
