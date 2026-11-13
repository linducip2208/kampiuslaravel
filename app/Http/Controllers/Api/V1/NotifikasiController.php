<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Mahasiswa;
use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notif = Notifikasi::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($notif);
    }

    public function read(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);

        $notifikasi->update(['dibaca_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function registerToken(Request $request): JsonResponse
    {
        // Alias legacy untuk POST /device-token; validasi longgar demi BC.
        $request->validate([
            'fcm_token' => ['required_without:device_token', 'nullable', 'string', 'max:500'],
            'device_token' => ['required_without:fcm_token', 'nullable', 'string', 'max:500'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        $token = $request->input('fcm_token') ?? $request->input('device_token');

        try {
            $request->user()->forceFill(['fcm_token' => $token])->save();
        } catch (\Throwable) {
            // Kolom legacy boleh tidak ada; device_tokens adalah sumber utama.
        }

        $topic = null;

        try {
            $prodiId = Mahasiswa::where('user_id', $request->user()->id)->value('prodi_id');
            $topic = $prodiId ? "prodi_{$prodiId}" : null;

            DeviceToken::updateOrCreate(
                ['token' => $token],
                [
                    'user_id' => $request->user()->id,
                    'platform' => $request->input('platform', 'android'),
                    'topic' => $topic,
                    'app_version' => $request->input('app_version'),
                ]
            );
        } catch (\Throwable) {
            // Tabel device_tokens boleh belum termigrasi di env lama.
        }

        return response()->json(['ok' => true, 'topic' => $topic]);
    }
}
