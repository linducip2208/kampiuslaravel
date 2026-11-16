<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengumumanResource;
use App\Models\Pengumuman;
use App\Support\ApiPaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target' => ['nullable', 'string', 'max:50'],
        ]);

        if (! class_exists(Pengumuman::class)) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        try {
            $query = ApiPaging::search(
                Pengumuman::query()
                    ->where(function ($q): void {
                        $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                    }),
                $request->string('q')->toString(),
                'judul',
                'isi'
            );

            if (! empty($validated['target'])) {
                $query->whereIn('target', ['semua', $validated['target']]);
            }

            $result = ApiPaging::paginate($query, $request, ['id', 'published_at'], '-published_at');

            return response()->json([
                'data' => PengumumanResource::collection($result),
                'meta' => ApiPaging::meta($result),
            ]);
        } catch (\Throwable) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }
    }
}
