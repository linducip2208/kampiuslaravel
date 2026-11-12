<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengumumanResource;
use App\Models\Pengumuman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (! class_exists(Pengumuman::class)) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        try {
            $query = Pengumuman::query()
                ->where(function ($q): void {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->latest('published_at');

            if (! empty($validated['target'])) {
                $query->whereIn('target', ['semua', $validated['target']]);
            }

            $result = $query->paginate($validated['per_page'] ?? 15);

            return response()->json([
                'data' => PengumumanResource::collection($result),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'total' => $result->total(),
                ],
            ]);
        } catch (\Throwable) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }
    }
}
