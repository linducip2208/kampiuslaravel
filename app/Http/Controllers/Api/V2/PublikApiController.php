<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Models\Prodi;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API publik v2 (read-only, throttle) + webhook masuk terverifikasi HMAC.
 *
 * - Kunci publik: tanpa token, dibatasi throttle:60,1.
 * - Webhook: POST /v2/webhook/terima, header X-Webhook-Signature =
 *   hash_hmac('sha256', raw_body, settings `webhook.secret`).
 */
class PublikApiController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'app' => config('app.name'),
            'api' => 'v2',
            'time' => now()->toIso8601String(),
        ]);
    }

    public function prodi(): JsonResponse
    {
        $data = Prodi::query()
            ->select(['kode', 'nama', 'jenjang', 'slug'])
            ->orderBy('nama')
            ->paginate(20);

        return response()->json($data);
    }

    public function pengumuman(): JsonResponse
    {
        $data = Pengumuman::query()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->select(['id', 'judul', 'isi', 'published_at'])
            ->paginate(20);

        return response()->json($data);
    }

    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) Setting::get('webhook.secret', '');

        if (strlen($secret) < 16) {
            return response()->json(['message' => 'Integrasi webhook belum dikonfigurasi di halaman Integrasi (secret min. 16 karakter).'], 503);
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Signature webhook tidak valid.'], 400);
        }

        $validated = $request->validate([
            'event' => 'required|string|max:80',
            'payload' => 'nullable|array',
        ]);

        Log::info('webhook.v2.received', [
            'event' => $validated['event'],
            'payload_keys' => array_keys($validated['payload'] ?? []),
        ]);

        return response()->json(['ok' => true, 'event' => $validated['event']]);
    }
}
