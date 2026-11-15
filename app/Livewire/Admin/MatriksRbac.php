<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC batch 3: matriks peran × permission (baca-saja, dari DB).
 * Sumber kebenaran tertulis: docs/FINAL_RBAC_MATRIX.md.
 */
class MatriksRbac extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public function render()
    {
        $permissions = Permission::orderBy('name')->pluck('name')->all();
        $roles = Role::with('permissions')->orderBy('name')->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'milik' => $r->permissions->pluck('name')->flip()->all(),
            ]);

        return view('livewire.admin.matriks-rbac', compact('permissions', 'roles'));
    }
}
