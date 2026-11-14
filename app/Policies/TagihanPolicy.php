<?php

namespace App\Policies;

use App\Models\Tagihan;
use App\Models\User;

/**
 * Otorisasi Tagihan — cermin AuthorizesMahasiswaData sebagai Policy.
 * Staf boleh lihat semua; mahasiswa hanya miliknya sendiri.
 */
class TagihanPolicy
{
    /** @var list<string> */
    private const STAF = ['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'pimpinan'];

    public function view(User $user, Tagihan $tagihan): bool
    {
        if ($user->hasAnyRole(self::STAF)) {
            return true;
        }

        $ownId = $user->mahasiswa?->id;

        return $ownId !== null && $ownId === (int) $tagihan->mahasiswa_id;
    }

    /**
     * Aksi tulis (buat VA / bukti manual): pemilik + staf keuangan/AK.
     * Dosen/pimpinan boleh LIHAT (view) tapi tidak boleh membuat pembayaran.
     */
    public function bayar(User $user, Tagihan $tagihan): bool
    {
        if ($user->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi'])) {
            return true;
        }

        $ownId = $user->mahasiswa?->id;

        return $ownId !== null && $ownId === (int) $tagihan->mahasiswa_id;
    }

    public function verify(User $user, Tagihan $tagihan): bool
    {
        return $user->hasAnyRole(['keuangan', 'superadmin']);
    }
}
