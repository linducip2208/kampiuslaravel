<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Daftarkan FCM/device token + topik FCM per prodi (prodi_{id}).
     * Idempoten: token yang sama di-upsert, bukan duplikat.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required_without:device_token', 'nullable', 'string', 'max:500'],
            'device_token' => ['required_without:fcm_token', 'nullable', 'string', 'max:500'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        $token = $validated['fcm_token'] ?? $validated['device_token'];

        $topic = null;
        if (class_exists(Mahasiswa::class)) {
            $prodiId = Mahasiswa::where('user_id', $request->user()->id)->value('prodi_id');
            $topic = $prodiId ? "prodi_{$prodiId}" : null;
        }

        $record = DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'] ?? 'android',
                'topic' => $topic,
                'app_version' => $validated['app_version'] ?? null,
            ]
        );

        // Sinkronkan kolom legacy users.fcm_token bila ada.
        try {
            $request->user()->forceFill(['fcm_token' => $token])->save();
        } catch (\Throwable) {
            // Kolom legacy boleh tidak ada; device_tokens adalah sumber utama.
        }

        return response()->json([
            'ok' => true,
            'topic' => $record->topic,
            'platform' => $record->platform,
        ], 201);
    }
}
