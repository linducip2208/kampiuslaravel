<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Mahasiswa;
use App\Models\Tugas;
use App\Models\TugasKumpul;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class KumpulTugas extends Component
{
    use RequiresRoles;

    /** Otorisasi owner-scope dicek manual di mount(). */
    public array $allowedRoles = [];

    use WithFileUploads;

    public int $tugasId;

    public int $mahasiswaId;

    public $file;

    public function mount(int $tugasId, int $mahasiswaId): void
    {
        // Mahasiswa hanya boleh kumpul untuk dirinya sendiri (anti-spoofing).
        $user = Auth::user();
        if ($user && $user->hasRole('mahasiswa') && ! $user->hasAnyRole(['superadmin', 'admin_prodi', 'dosen'])) {
            $mahasiswaId = (int) Mahasiswa::where('user_id', $user->id)->value('id');
        }

        $this->tugasId = $tugasId;
        $this->mahasiswaId = $mahasiswaId;
    }

    public function save(): void
    {
        $this->validate(['file' => 'required|file|max:10240|mimes:pdf,doc,docx,zip,jpg,jpeg,png']);

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
