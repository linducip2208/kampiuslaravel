<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KrsController extends Controller
{
    public function aktif(Request $request): JsonResponse
    {
        if (! class_exists(Krs::class) || ! class_exists(Mahasiswa::class)) {
            return response()->json(['data' => null]);
        }

        try {
            $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

            if (! $mhs) {
                return response()->json(['data' => null]);
            }

            $krs = Krs::where('mahasiswa_id', $mhs->id)
                ->with(['details.kelas.matakuliah'])
                ->latest('id')
                ->first();

            if (! $krs) {
                return response()->json(['data' => null]);
            }

            return response()->json(['data' => $krs]);
        } catch (\Throwable) {
            return response()->json(['data' => null]);
        }
    }
}
