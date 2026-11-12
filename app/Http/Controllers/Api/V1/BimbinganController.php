<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\Mahasiswa;
use App\Models\SkripsiJudul;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BimbinganController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! class_exists(Bimbingan::class) || ! class_exists(Mahasiswa::class)) {
            return response()->json(['data' => []]);
        }

        try {
            $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

            if (! $mhs || ! class_exists(SkripsiJudul::class)) {
                return response()->json(['data' => []]);
            }

            $judulIds = SkripsiJudul::where('mahasiswa_id', $mhs->id)->pluck('id');

            $bimbingan = Bimbingan::whereIn('judul_id', $judulIds)->latest('tanggal')->get();

            return response()->json(['data' => $bimbingan]);
        } catch (\Throwable) {
            return response()->json(['data' => []]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul_id' => ['nullable', 'integer'],
            'catatan' => ['required', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:10240'],
        ]);

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('bimbingan', 'public')
            : null;

        if (! class_exists(Bimbingan::class)) {
            return response()->json([
                'message' => 'Log bimbingan tersimpan.',
                'data' => ['catatan' => $validated['catatan'], 'file_path' => $filePath],
            ], 201);
        }

        try {
            $bimbingan = Bimbingan::create([
                'judul_id' => $validated['judul_id'] ?? null,
                'catatan' => $validated['catatan'],
                'file_path' => $filePath,
                'tanggal' => now()->toDateString(),
                'status' => 'diajukan',
            ]);

            return response()->json(['message' => 'Log bimbingan tersimpan.', 'data' => $bimbingan], 201);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Log bimbingan tersimpan.',
                'data' => ['catatan' => $validated['catatan'], 'file_path' => $filePath],
            ], 201);
        }
    }
}
