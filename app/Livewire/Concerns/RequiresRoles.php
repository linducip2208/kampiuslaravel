<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Otorisasi server-side untuk aksi Livewire.
 * Update-request Livewire TIDAK melewati route middleware,
 * jadi setiap komponen sensitif wajib memakai trait ini.
 *
 * Pakai: use RequiresRoles; + public array $allowedRoles = [...];
 * Kosongkan $allowedRoles bila otorisasi diatur manual (owner-scope).
 */
trait RequiresRoles
{
    public function bootRequiresRoles(): void
    {
        if (! Auth::check()) {
            abort(401);
        }

        /** @var list<string> $roles */
        $roles = property_exists($this, 'allowedRoles') ? (array) $this->allowedRoles : [];

        if ($roles !== [] && ! Auth::user()->hasAnyRole($roles)) {
            abort(403, 'Anda tidak berhak melakukan aksi ini.');
        }
    }
}
