<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * CRUD Dosen & Tendik (satu komponen, dua tab).
 *
 * Dosen: baris tabel `dosen` + akun user (NIDN unique, relasi prodi,
 * status aktif/nonaktif). Tendik: user dengan peran staf
 * (superadmin/admin_prodi/keuangan/pimpinan), tanpa tabel tambahan.
 */
class DosenData extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $tab = 'dosen';

    public ?int $editId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public string $search = '';

    public string $message = '';

    /** @return list<string> */
    public static function peranTendik(): array
    {
        return ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'];
    }

    public function gantiTab(string $tab): void
    {
        abort_unless(in_array($tab, ['dosen', 'tendik'], true), 404);

        $this->tab = $tab;
        $this->reset(['editId', 'form', 'search', 'message']);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        if ($this->tab === 'tendik') {
            $user = User::findOrFail($id);

            $this->editId = $user->id;
            $this->form = [
                'nama' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'admin_prodi',
            ];

            return;
        }

        $dosen = Dosen::with('user')->findOrFail($id);

        $this->editId = $dosen->id;
        $this->form = [
            'nama' => $dosen->user?->name ?? '',
            'email' => $dosen->user?->email ?? '',
            'nidn' => $dosen->nidn,
            'prodi_id' => $dosen->prodi_id,
            'status' => $dosen->status,
        ];
    }

    public function simpan(): void
    {
        $this->tab === 'tendik' ? $this->simpanTendik() : $this->simpanDosen();
    }

    private function simpanDosen(): void
    {
        $dosen = $this->editId ? Dosen::findOrFail($this->editId) : null;
        $userId = $dosen?->user_id;

        $this->validate([
            'form.nama' => ['required', 'string', 'max:100'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'form.nidn' => ['required', 'string', 'max:20', Rule::unique('dosen', 'nidn')->ignore($this->editId)],
            'form.prodi_id' => ['required', 'exists:prodi,id'],
            'form.status' => ['required', 'in:aktif,nonaktif'],
        ]);

        DB::transaction(function () use ($dosen) {
            $user = User::updateOrCreate(
                ['id' => $dosen?->user_id ?? 0],
                [
                    'name' => $this->form['nama'],
                    'email' => $this->form['email'],
                    'password' => $dosen?->user->password ?? Hash::make(Str::random(12)),
                ]
            );

            if (! $user->hasRole('dosen')) {
                $user->assignRole('dosen');
            }

            Dosen::updateOrCreate(
                ['id' => $this->editId ?? 0],
                [
                    'user_id' => $user->id,
                    'nidn' => $this->form['nidn'],
                    'prodi_id' => $this->form['prodi_id'],
                    'status' => $this->form['status'],
                ]
            );
        });

        $this->reset(['editId', 'form']);
        $this->message = 'Data dosen tersimpan.';
    }

    private function simpanTendik(): void
    {
        $this->validate([
            'form.nama' => ['required', 'string', 'max:100'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editId)],
            'form.role' => ['required', Rule::in(self::peranTendik())],
        ]);

        DB::transaction(function () {
            $user = User::updateOrCreate(
                ['id' => $this->editId ?? 0],
                [
                    'name' => $this->form['nama'],
                    'email' => $this->form['email'],
                    'password' => $this->editId
                        ? User::findOrFail($this->editId)->password
                        : Hash::make(Str::random(12)),
                ]
            );

            $user->syncRoles([$this->form['role']]);
        });

        $this->reset(['editId', 'form']);
        $this->message = 'Data tendik tersimpan.';
    }

    public function hapus(int $id): void
    {
        try {
            if ($this->tab === 'tendik') {
                User::findOrFail($id)->delete();
                $this->message = 'Data tendik dihapus.';
            } else {
                $dosen = Dosen::findOrFail($id);
                DB::transaction(function () use ($dosen) {
                    $userId = $dosen->user_id;
                    $dosen->delete();
                    User::whereKey($userId)->delete();
                });
                $this->message = 'Data dosen dihapus.';
            }
        } catch (\Throwable) {
            $this->addError('form.hapus', 'Data dipakai modul lain, tidak bisa dihapus (nonaktifkan statusnya).');
        }
    }

    public function render()
    {
        if ($this->tab === 'tendik') {
            $rows = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', self::peranTendik()))
                ->when($this->search !== '', fn ($q) => $q->where(
                    fn ($w) => $w->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                ))
                ->with('roles')
                ->latest('id')
                ->paginate(10);
        } else {
            $rows = Dosen::query()
                ->with(['user', 'prodi'])
                ->when($this->search !== '', fn ($q) => $q->where('nidn', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', fn ($w) => $w->where('name', 'like', '%'.$this->search.'%')))
                ->latest('id')
                ->paginate(10);
        }

        return view('livewire.admin.dosen-data', [
            'rows' => $rows,
            'prodis' => Prodi::orderBy('nama')->get(['id', 'nama']),
            'peranTendik' => self::peranTendik(),
        ]);
    }
}
