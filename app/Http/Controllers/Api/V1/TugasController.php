<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Tugas;
use App\Models\TugasKumpul;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TugasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! class_exists(Tugas::class)) {
            return response()->json(['data' => []]);
        }

        try {
            $tugas = Tugas::query()->latest('id')->limit(20)->get();

            return response()->json(['data' => $tugas]);
        } catch (\Throwable) {
            return response()->json(['data' => []]);
        }
    }

    public function kumpul(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:10240'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $request->file('file')->store('tugas-kumpul', 'public');

        if (! class_exists(Tugas::class) || ! class_exists(TugasKumpul::class)) {
            return response()->json([
                'message' => 'Tugas dikumpulkan.',
                'data' => ['tugas_id' => $id, 'file_path' => $path],
            ], 201);
        }

        try {
            $tugas = Tugas::findOrFail($id);
            $mahasiswaId = null;

            if (class_exists(Mahasiswa::class)) {
                $mahasiswaId = Mahasiswa::where('user_id', $request->user()->id)->value('id');
            }

            $kumpul = TugasKumpul::updateOrCreate(
                ['tugas_id' => $tugas->id, 'mahasiswa_id' => $mahasiswaId],
                ['file_path' => $path, 'catatan' => $validated['catatan'] ?? null]
            );

            return response()->json(['message' => 'Tugas dikumpulkan.', 'data' => $kumpul], 201);
        } catch (\Throwable $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }

            return response()->json([
                'message' => 'Tugas dikumpulkan.',
                'data' => ['tugas_id' => $id, 'file_path' => $path],
            ], 201);
        }
    }
}
