<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Models\Prodi;
use App\Models\Setting;
use App\Models\WebhookEvent;
use App\Support\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        $pending = -1;
        $db = 'down';

        try {
            DB::select('select 1');
            $db = 'up';
            $ran = app('migrator')->getRepository()->getRan();
            $files = collect(glob(database_path('migrations/*.php')) ?: [])
                ->map(fn ($f) => basename($f, '.php'))->all();
            $pending = count(array_diff($files, $ran));
        } catch (Throwable) {
            // biarkan default down/-1
        }

        return response()->json(ApiEnvelope::ok([
            'app' => config('app.name'),
            'api' => 'v2',
            'time' => now()->toIso8601String(),
            'db' => $db,
            'pending_migrations' => $pending,
        ]));
    }

    public function prodi(): JsonResponse
    {
        $data = Prodi::query()
            ->select(['kode', 'nama', 'jenjang', 'slug'])
            ->orderBy('nama')
            ->paginate(20);

        return response()->json(ApiEnvelope::ok($data->toArray()));
    }

    public function pengumuman(): JsonResponse
    {
        $data = Pengumuman::query()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->select(['id', 'judul', 'isi', 'published_at'])
            ->paginate(20);

        return response()->json(ApiEnvelope::ok($data->toArray()));
    }

    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) Setting::get('webhook.secret', '');

        if (strlen($secret) < 16) {
            return response()->json(ApiEnvelope::fail('Integrasi webhook belum dikonfigurasi di halaman Integrasi (secret min. 16 karakter).'), 503);
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(ApiEnvelope::fail('Signature webhook tidak valid.'), 400);
        }

        $validated = $request->validate([
            'event' => 'required|string|max:80',
            'payload' => 'nullable|array',
        ]);

        // M-017: idempotency-key + event-log. Replay signature sama → 200 tanpa efek ganda.
        $key = 'v2:'.hash('sha256', $signature.'|'.$request->getContent());
        $existing = WebhookEvent::where('idempotency_key', $key)->first();

        if ($existing) {
            return response()->json(['ok' => true, 'event' => $existing->event, 'deduped' => true]);
        }

        $stored = WebhookEvent::create([
            'sumber' => 'v2',
            'event' => $validated['event'],
            'idempotency_key' => $key,
            'payload_json' => ['payload_keys' => array_keys($validated['payload'] ?? [])],
        ]);

        Log::info('webhook.v2.received', [
            'event' => $validated['event'],
            'payload_keys' => array_keys($validated['payload'] ?? []),
        ]);

        return response()->json(['ok' => true, 'event' => $stored->event]);
    }
}
