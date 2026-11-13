<?php

namespace App\Services;

use App\Models\HelpdeskSla;
use App\Models\Tiket;
use App\Models\User;

class HelpdeskSlaService
{
    public static function apply(Tiket $tiket): Tiket
    {
        $sla = HelpdeskSla::target($tiket->kategori ?? 'umum', $tiket->prioritas ?? 'normal');

        $tiket->forceFill([
            'due_respon_at' => $sla ? now()->addHours($sla->respon_jam) : now()->addDay(),
            'due_resolusi_at' => $sla ? now()->addHours($sla->resolusi_jam) : now()->addDays(3),
        ])->save();

        return $tiket->fresh();
    }

    public static function cekEskalasi(Tiket $tiket): bool
    {
        if ($tiket->status === 'selesai' || $tiket->dieskalasi) {
            return false;
        }

        $now = now();
        $lewat = ($tiket->due_respon_at && $now->greaterThan($tiket->due_respon_at) && $tiket->status === 'terbuka')
            || ($tiket->due_resolusi_at && $now->greaterThan($tiket->due_resolusi_at));

        if (! $lewat) {
            return false;
        }

        $sla = HelpdeskSla::target($tiket->kategori ?? 'umum', $tiket->prioritas ?? 'normal');
        $role = $sla->eskalasi_role ?? 'superadmin';

        $tiket->forceFill(['dieskalasi' => true])->save();

        User::role($role)->chunk(50, function ($users) use ($tiket) {
            foreach ($users as $user) {
                app(NotifikasiService::class)->send($user, 'Eskalasi tiket #'.$tiket->id, 'Tiket "'.$tiket->subjek.'" melewati SLA, mohon ditindaklanjuti.');
            }
        });

        return true;
    }
}
