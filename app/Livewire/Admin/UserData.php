<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserData extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $search = '';

    public string $roleFilter = '';

    public ?int $editId = null;

    /** @var array{name:string,email:string,password:string,role:string} */
    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'role' => 'mahasiswa',
    ];

    public string $message = '';

    /** @return list<string> */
    public static function daftarRole(): array
    {
        // Dinamis dari DB agar peran baru seeder langsung bisa ditetapkan
        // (validasi simpan() memakai daftar yang sama — selalu sinkron).
        try {
            return Role::orderBy('name')->pluck('name')->all();
        } catch (\Throwable) {
            return ['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'mahasiswa', 'pimpinan'];
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        $this->editId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->roles->first()?->name ?? 'mahasiswa',
        ];
        $this->message = '';
    }

    public function batal(): void
    {
        $this->reset(['editId', 'form', 'message']);
        $this->form = ['name' => '', 'email' => '', 'password' => '', 'role' => 'mahasiswa'];
    }

    public function simpan(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editId),
            ],
            'form.password' => $this->editId ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'],
            'form.role' => ['required', Rule::in(self::daftarRole())],
        ]);

        $data = [
            'name' => $this->form['name'],
            'email' => $this->form['email'],
        ];

        if ($this->form['password'] !== '') {
            $data['password'] = Hash::make($this->form['password']);
        } elseif (! $this->editId) {
            $data['password'] = Hash::make($this->form['password']);
        }

        $user = User::updateOrCreate(['id' => $this->editId ?? 0], $data);
        $user->syncRoles([$this->form['role']]);

        $this->batal();
        $this->message = 'User tersimpan.';
    }

    public function hapus(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->addError('form.hapus', 'Tidak bisa menghapus akun sendiri.');

            return;
        }

        $user->delete();
        $this->resetPage();
        $this->message = 'User dihapus.';
    }

    public function resetPassword(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['password' => Hash::make('Password123')]);
        $this->message = 'Password direset ke Password123 untuk '.$user->email.'.';
    }

    public function render()
    {
        $query = User::with('roles')->orderByDesc('id');

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->roleFilter !== '') {
            $query->role($this->roleFilter);
        }

        return view('livewire.admin.user-data', [
            'rows' => $query->paginate(10),
            'roles' => Role::whereIn('name', self::daftarRole())->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
