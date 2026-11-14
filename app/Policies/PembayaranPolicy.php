<?php

namespace App\Policies;

use App\Models\Pembayaran;
use App\Models\User;

/**
 * Otorisasi Pembayaran — delegasi kepemilikan ke TagihanPolicy.
 */
class PembayaranPolicy
{
    public function view(User $user, Pembayaran $pembayaran): bool
    {
        $tagihan = $pembayaran->tagihan;

        if (! $tagihan) {
            return false;
        }

        return app(TagihanPolicy::class)->view($user, $tagihan);
    }

    public function refund(User $user, Pembayaran $pembayaran): bool
    {
        return $user->hasAnyRole(['keuangan', 'superadmin']);
    }
}
