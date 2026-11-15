<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Otorisasi kepemilikan data mahasiswa.
 * Staf (superadmin/admin_prodi/keuangan/dosen/pimpinan + kaprodi/kajur/
 * rektor/warek setara) boleh akses semua; peran lain (termasuk kasir,
 * akuntansi, dekan, auditor, mahasiswa) hanya miliknya sendiri.
 * Menyembunyikan tombol BUKAN otorisasi.
 */
trait AuthorizesMahasiswaData
{
    protected function authorizeMahasiswa(int $mahasiswaId): void
    {
        $user = Auth::user();

        abort_unless($user, 401);

        if ($user->hasAnyRole(['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'pimpinan', 'kaprodi', 'kajur', 'rektor', 'warek'])) {
            return;
        }

        $ownId = $user->mahasiswa?->id;

        abort_unless($ownId && $ownId === $mahasiswaId, 403, 'Anda tidak berhak mengakses data ini.');
    }
}
