<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Mahasiswa;
use App\Models\Pertemuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => ['required', 'string', 'max:255'],
        ]);

        if (! class_exists(Pertemuan::class)) {
            return response()->json(['message' => 'QR tidak valid atau kedaluwarsa.'], 404);
        }

        try {
            $pertemuan = Pertemuan::where('qr_token', $validated['qr_token'])->first();

            if (! $pertemuan) {
                return response()->json(['message' => 'QR tidak valid atau kedaluwarsa.'], 404);
            }

            if ($pertemuan->qr_expires_at && now()->greaterThan($pertemuan->qr_expires_at)) {
                return response()->json(['message' => 'QR kedaluwarsa.'], 422);
            }

            if (class_exists(Absensi::class) && class_exists(Mahasiswa::class)) {
                $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

                if ($mhs) {
                    $absensi = Absensi::updateOrCreate(
                        ['pertemuan_id' => $pertemuan->id, 'mahasiswa_id' => $mhs->id],
                        ['status' => 'hadir', 'scanned_at' => now()]
                    );

                    return response()->json(['message' => 'Absensi tercatat.', 'data' => $absensi], 201);
                }
            }

            return response()->json(['message' => 'QR valid.', 'data' => ['pertemuan_id' => $pertemuan->id]]);
        } catch (\Throwable) {
            return response()->json(['message' => 'QR tidak valid atau kedaluwarsa.'], 404);
        }
    }
}
