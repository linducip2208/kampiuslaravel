<?php

namespace App\Livewire\Lms;

use App\Models\Tugas;
use App\Models\TugasKumpul;
use Livewire\Component;
use Livewire\WithFileUploads;

class KumpulTugas extends Component
{
    use WithFileUploads;

    public int $tugasId;

    public int $mahasiswaId;

    public $file;

    public function mount(int $tugasId, int $mahasiswaId): void
    {
        $this->tugasId = $tugasId;
        $this->mahasiswaId = $mahasiswaId;
    }

    public function save(): void
    {
        $this->validate(['file' => 'required|file|max:10240']);

        $tugas = Tugas::findOrFail($this->tugasId);
        if ($tugas->deadline && now()->greaterThan($tugas->deadline)) {
            $this->addError('file', 'Deadline sudah lewat.');

            return;
        }

        $path = $this->file->store('tugas_kumpul', 'public');

        TugasKumpul::updateOrCreate(
            ['tugas_id' => $this->tugasId, 'mahasiswa_id' => $this->mahasiswaId],
            ['file_path' => $path]
        );

        $this->reset('file');
        session()->flash('success', 'Tugas berhasil dikumpulkan.');
    }

    public function render()
    {
        return view('livewire.lms.kumpul-tugas', [
            'tugas' => Tugas::find($this->tugasId),
            'pengumpulan' => TugasKumpul::where('tugas_id', $this->tugasId)
                ->where('mahasiswa_id', $this->mahasiswaId)->first(),
        ]);
    }
}
