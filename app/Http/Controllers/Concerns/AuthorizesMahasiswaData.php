<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Otorisasi kepemilikan data mahasiswa.
 * Staf (superadmin/admin_prodi/keuangan/dosen/pimpinan) boleh akses semua;
 * mahasiswa hanya miliknya sendiri. Menyembunyikan tombol BUKAN otorisasi.
 */
trait AuthorizesMahasiswaData
{
    protected function authorizeMahasiswa(int $mahasiswaId): void
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->hasAnyRole(['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'pimpinan'])) {
            return;
        }

        $ownId = $user->mahasiswa?->id;

        abort_unless($ownId && $ownId === $mahasiswaId, 403, 'Anda tidak berhak mengakses data ini.');
    }
}
