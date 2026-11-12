<?php

namespace App\Livewire\Master;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Matakuliah;
use App\Models\Prodi;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRUD data master akademik (satu komponen, multi-entitas).
 */
class Data extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $entitas = 'prodi';

    public ?int $editId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public string $search = '';

    public string $message = '';

    /** @return array<string, array{model: class-string, judul: string, kolom: array<string, string>}> */
    public static function definisi(): array
    {
        return [
            'fakultas' => ['model' => Fakultas::class, 'judul' => 'Fakultas', 'kolom' => ['nama' => 'text', 'kode' => 'text']],
            'prodi' => ['model' => Prodi::class, 'judul' => 'Program Studi', 'kolom' => ['fakultas_id' => 'fakultas', 'nama' => 'text', 'jenjang' => 'text', 'kode' => 'text']],
            'tahun' => ['model' => TahunAkademik::class, 'judul' => 'Tahun Akademik', 'kolom' => ['tahun' => 'text', 'semester' => 'text', 'aktif' => 'bool']],
            'ruang' => ['model' => Ruang::class, 'judul' => 'Ruang', 'kolom' => ['gedung' => 'text', 'nama' => 'text', 'kapasitas' => 'number']],
            'matakuliah' => ['model' => Matakuliah::class, 'judul' => 'Matakuliah', 'kolom' => ['prodi_id' => 'prodi', 'kode' => 'text', 'nama' => 'text', 'sks' => 'number', 'semester' => 'number']],
            'kelas' => ['model' => Kelas::class, 'judul' => 'Kelas', 'kolom' => ['matakuliah_id' => 'matakuliah', 'tahun_akademik_id' => 'tahun', 'nama' => 'text', 'kuota' => 'number', 'hari' => 'text', 'jam_mulai' => 'time', 'jam_selesai' => 'time']],
        ];
    }

    public function gantiEntitas(string $entitas): void
    {
        abort_unless(array_key_exists($entitas, self::definisi()), 404);

        $this->entitas = $entitas;
        $this->reset(['editId', 'form', 'search', 'message']);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $def = self::definisi()[$this->entitas];
        $row = $def['model']::findOrFail($id);

        $this->editId = $row->id;
        $this->form = $row->only(array_keys($def['kolom']));
    }

    public function simpan(): void
    {
        $def = self::definisi()[$this->entitas];
        $rules = [];

        foreach ($def['kolom'] as $field => $tipe) {
            $rules["form.{$field}"] = match ($tipe) {
                'number' => ['required', 'integer', 'min:0'],
                'bool' => ['nullable', 'boolean'],
                'time' => ['nullable', 'date_format:H:i'],
                'fakultas' => ['required', 'exists:fakultas,id'],
                'prodi' => ['required', 'exists:prodi,id'],
                'matakuliah' => ['required', 'exists:matakuliah,id'],
                'tahun' => ['required', 'exists:tahun_akademik,id'],
                default => ['required', 'string', 'max:100'],
            };
        }

        $this->validate($rules);

        $data = $this->form;
        if (($def['kolom']['slug'] ?? null) === 'text' || $this->entitas === 'prodi') {
            $data['slug'] = Str::slug(($data['nama'] ?? '').'-'.($data['kode'] ?? ''));
        }

        $model = $def['model'];
        if ($this->entitas === 'tahun' && ! empty($data['aktif'])) {
            $model::query()->update(['aktif' => false]);
        }

        $model::updateOrCreate(['id' => $this->editId ?? 0], $data);

        $this->reset(['editId', 'form']);
        $this->message = 'Data tersimpan.';
    }

    public function hapus(int $id): void
    {
        $def = self::definisi()[$this->entitas];
        $row = $def['model']::findOrFail($id);

        try {
            $row->delete();
            $this->message = 'Data dihapus.';
        } catch (\Throwable) {
            $this->addError('form.hapus', 'Data dipakai modul lain, tidak bisa dihapus (arsipkan dengan menonaktifkan).');
        }
    }

    public function render()
    {
        $def = self::definisi()[$this->entitas];
        $query = $def['model']::query();

        if ($this->search !== '') {
            $query->where('nama', 'like', '%'.$this->search.'%');
        }

        return view('livewire.master.data', [
            'definisi' => self::definisi(),
            'judul' => $def['judul'],
            'kolom' => $def['kolom'],
            'rows' => $query->latest('id')->paginate(10),
            'fakultas' => Fakultas::orderBy('nama')->get(['id', 'nama']),
            'prodis' => Prodi::orderBy('nama')->get(['id', 'nama']),
            'matakuliahs' => Matakuliah::orderBy('kode')->get(['id', 'kode', 'nama']),
            'tahuns' => TahunAkademik::orderByDesc('id')->get(['id', 'tahun', 'semester']),
        ]);
    }
}
