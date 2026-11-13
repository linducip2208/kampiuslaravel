<?php

namespace App\Livewire;

use App\Livewire\Concerns\RequiresRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profil extends Component
{
    use RequiresRoles;

    /** @var list<string> */
    public array $allowedRoles = [];

    public string $name = '';

    public string $email = '';

    public string $passwordSaatIni = '';

    public string $passwordBaru = '';

    public string $passwordBaruConfirmation = '';

    public string $messageProfil = '';

    public string $messagePassword = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
    }

    public function simpanProfil(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update(['name' => $this->name, 'email' => $this->email]);

        $this->messageProfil = 'Profil tersimpan.';
        $this->messagePassword = '';
    }

    public function gantiPassword(): void
    {
        $this->validate([
            'passwordSaatIni' => ['required', 'current_password'],
            'passwordBaru' => ['required', 'string', 'min:8', 'confirmed:passwordBaruConfirmation'],
        ], [], [
            'passwordSaatIni' => 'password saat ini',
            'passwordBaru' => 'password baru',
        ]);

        Auth::user()->update(['password' => Hash::make($this->passwordBaru)]);
        $this->reset(['passwordSaatIni', 'passwordBaru', 'passwordBaruConfirmation']);
        $this->messagePassword = 'Password berhasil diganti.';
        $this->messageProfil = '';
    }

    public function render()
    {
        return view('livewire.profil');
    }
}
