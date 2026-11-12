<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
        $request->validate(['fcm_token' => 'required|string|max:500']);

        $request->user()->forceFill(['fcm_token' => $request->fcm_token])->save();

        return response()->json(['ok' => true]);
    }
}
