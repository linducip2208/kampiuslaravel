<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Materi;
use Livewire\Component;
use Livewire\WithFileUploads;

class MateriCrud extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    use WithFileUploads;

    public int $pertemuanId;

    public string $judul = '';

    public string $link = '';

    public string $deskripsi = '';

    public $file;

    public ?int $editId = null;

    /** ID materi yang sedang dipratinjau sebaris (null = tidak ada). */
    public ?int $pratinjauId = null;

    protected function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'link' => 'nullable|url|max:500',
            'deskripsi' => 'nullable|string',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,mp4',
        ];
    }

    public function mount(int $pertemuanId): void
    {
        $this->pertemuanId = $pertemuanId;
    }

    public function save(): void
    {
        $this->validate();

        $path = null;
        if ($this->file) {
            $path = $this->file->store('materi', 'public');
        }

        $data = [
            'pertemuan_id' => $this->pertemuanId,
            'judul' => $this->judul,
            'link' => $this->link ?: null,
            'deskripsi' => $this->deskripsi ?: null,
        ];
        if ($path) {
            $data['file_path'] = $path;
        }

        if ($this->editId) {
            Materi::whereKey($this->editId)->update($data);
        } else {
            Materi::create($data);
        }

        $this->reset(['judul', 'link', 'deskripsi', 'file', 'editId']);
    }

    public function edit(int $id): void
    {
        $materi = Materi::findOrFail($id);
        $this->editId = $materi->id;
        $this->judul = $materi->judul;
        $this->link = (string) ($materi->link ?? '');
        $this->deskripsi = (string) ($materi->deskripsi ?? '');
    }

    public function delete(int $id): void
    {
        Materi::whereKey($id)->delete();

        if ($this->pratinjauId === $id) {
            $this->pratinjauId = null;
        }
    }

    /** Tampilkan/sembunyikan pratinjau PDF sebaris (tanpa unduh). */
    public function pratinjau(int $id): void
    {
        $this->pratinjauId = $this->pratinjauId === $id ? null : $id;
    }

    public function render()
    {
        return view('livewire.lms.materi-crud', [
            'items' => Materi::where('pertemuan_id', $this->pertemuanId)->latest()->get(),
        ]);
    }
}
