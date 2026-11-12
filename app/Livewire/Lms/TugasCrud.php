<?php

namespace App\Livewire\Lms;

use App\Models\Tugas;
use Livewire\Component;
use Livewire\WithPagination;

class TugasCrud extends Component
{
    use WithPagination;

    public int $kelasId;

    public string $judul = '';

    public string $deskripsi = '';

    public string $deadline = '';

    public ?int $editId = null;

    protected function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'nullable|date',
        ];
    }

    public function mount(int $kelasId): void
    {
        $this->kelasId = $kelasId;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'kelas_id' => $this->kelasId,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi ?: null,
            'deadline' => $this->deadline ?: null,
        ];

        if ($this->editId) {
            Tugas::whereKey($this->editId)->update($data);
        } else {
            Tugas::create($data);
        }

        $this->reset(['judul', 'deskripsi', 'deadline', 'editId']);
    }

    public function edit(int $id): void
    {
        $tugas = Tugas::findOrFail($id);
        $this->editId = $tugas->id;
        $this->judul = $tugas->judul;
        $this->deskripsi = (string) ($tugas->deskripsi ?? '');
        $this->deadline = $tugas->deadline?->format('Y-m-d\TH:i') ?? '';
    }

    public function delete(int $id): void
    {
        Tugas::whereKey($id)->delete();
    }

    public function render()
    {
        return view('livewire.lms.tugas-crud', [
            'items' => Tugas::where('kelas_id', $this->kelasId)->latest()->paginate(10),
        ]);
    }
}
