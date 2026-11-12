<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Pertemuan;
use Carbon\Carbon;

class AbsensiService
{
    /**
     * @return array{ok: bool, message: string, status?: string}
     */
    public static function scan(Pertemuan $pertemuan, int $mahasiswaId, string $token, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        if (! hash_equals($pertemuan->qr_token, $token)) {
            return ['ok' => false, 'message' => 'Token QR tidak valid.'];
        }

        if ($pertemuan->qr_expires_at && $now->greaterThan($pertemuan->qr_expires_at)) {
            return ['ok' => false, 'message' => 'Token QR kedaluwarsa.'];
        }

        if ($pertemuan->window_mulai && $now->lessThan($pertemuan->window_mulai)) {
            return ['ok' => false, 'message' => 'Window absensi belum dibuka.'];
        }

        if ($pertemuan->window_selesai && $now->greaterThan($pertemuan->window_selesai)) {
            return ['ok' => false, 'message' => 'Window absensi sudah ditutup.'];
        }

        $status = 'hadir';
        if ($pertemuan->window_mulai && $now->diffInMinutes($pertemuan->window_mulai) > 15) {
            $status = 'telat';
        }

        Absensi::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id, 'mahasiswa_id' => $mahasiswaId],
            ['status' => $status, 'scanned_at' => $now]
        );

        return ['ok' => true, 'message' => 'Absensi tercatat.', 'status' => $status];
    }
}
