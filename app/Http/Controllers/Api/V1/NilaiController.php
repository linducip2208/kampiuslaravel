<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NilaiResource;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NilaiController extends Controller
{
    public function ringkas(Request $request): JsonResponse
    {
        if (! class_exists(Nilai::class) || ! class_exists(Mahasiswa::class)) {
            return response()->json([
                'data' => [
                    'ipk' => 0,
                    'total_sks' => 0,
                    'per_semester' => [],
                    'nilai' => [],
                ],
            ]);
        }

        try {
            $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

            if (! $mhs) {
                return response()->json(['data' => ['ipk' => 0, 'total_sks' => 0, 'per_semester' => [], 'nilai' => []]]);
            }

            $nilai = Nilai::where('mahasiswa_id', $mhs->id)
                ->with(['kelas.matakuliah'])
                ->get();

            $totalBobotSks = 0.0;
            $totalSks = 0;

            foreach ($nilai as $n) {
                $sks = (int) ($n->kelas?->matakuliah?->sks ?? 0);
                $totalBobotSks += ((float) ($n->bobot ?? 0)) * $sks;
                $totalSks += $sks;
            }

            return response()->json([
                'data' => [
                    'ipk' => $totalSks > 0 ? round($totalBobotSks / $totalSks, 2) : 0,
                    'total_sks' => $totalSks,
                    'per_semester' => [],
                    'nilai' => NilaiResource::collection($nilai),
                ],
            ]);
        } catch (\Throwable) {
            return response()->json(['data' => ['ipk' => 0, 'total_sks' => 0, 'per_semester' => [], 'nilai' => []]]);
        }
    }
}
