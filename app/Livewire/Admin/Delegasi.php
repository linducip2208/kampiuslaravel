<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\DelegasiAkses;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * RBAC batch 4: delegasi peran berbatas waktu (UI admin).
 * Penerapan/ekspirasi oleh `delegasi:sinkron` (terjadwal harian).
 */
class Delegasi extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $dari_user_id = null;

    public string $ke_email = '';

    public string $role = 'mahasiswa';

    public string $mulai_at = '';

    public string $sampai_at = '';

    public string $keperluan = '';

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'dari_user_id' => 'required|exists:users,id',
            'ke_email' => 'required|email|exists:users,email',
            'role' => 'required|exists:roles,name',
            'mulai_at' => 'required|date',
            'sampai_at' => 'required|date|after_or_equal:mulai_at',
            'keperluan' => 'nullable|string|max:500',
        ]);

        abort_if((int) $this->dari_user_id === (int) User::where('email', $this->ke_email)->value('id'), 422, 'Tidak bisa mendelegasikan ke diri sendiri.');

        DelegasiAkses::create([
            'dari_user_id' => $this->dari_user_id,
            'ke_user_id' => User::where('email', $this->ke_email)->value('id'),
            'role' => $this->role,
            'mulai_at' => $this->mulai_at,
            'sampai_at' => $this->sampai_at,
            'keperluan' => $this->keperluan ?: null,
        ]);

        $this->reset(['dari_user_id', 'ke_email', 'role', 'mulai_at', 'sampai_at', 'keperluan']);
        $this->message = 'Delegasi tersimpan. Berlaku setelah `delegasi:sinkron` (jadwal harian).';
    }

    public function nonaktifkan(int $id): void
    {
        DelegasiAkses::findOrFail($id)->update(['status' => 'nonaktif']);
        $this->message = 'Delegasi dinonaktifkan.';
    }

    public function render()
    {
        return view('livewire.admin.delegasi', [
            'delegasis' => DelegasiAkses::with(['dari', 'ke'])->latest('id')->paginate(10),
            'roles' => Role::orderBy('name')->pluck('name')->all(),
        ]);
    }
}
