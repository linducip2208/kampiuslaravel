<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagihanResource;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! class_exists(Tagihan::class) || ! class_exists(Mahasiswa::class)) {
            return response()->json([
                'data' => [],
                'ringkasan' => ['total_tagihan' => 0, 'total_terbayar' => 0, 'total_menunggak' => 0],
            ]);
        }

        try {
            $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

            if (! $mhs) {
                return response()->json([
                    'data' => [],
                    'ringkasan' => ['total_tagihan' => 0, 'total_terbayar' => 0, 'total_menunggak' => 0],
                ]);
            }

            $tagihan = Tagihan::where('mahasiswa_id', $mhs->id)->latest('id')->get();

            $total = (float) $tagihan->sum('nominal');
            $terbayar = (float) $tagihan->sum('terbayar');

            return response()->json([
                'data' => TagihanResource::collection($tagihan),
                'ringkasan' => [
                    'total_tagihan' => $total,
                    'total_terbayar' => $terbayar,
                    'total_menunggak' => $total - $terbayar,
                ],
            ]);
        } catch (\Throwable) {
            return response()->json([
                'data' => [],
                'ringkasan' => ['total_tagihan' => 0, 'total_terbayar' => 0, 'total_menunggak' => 0],
            ]);
        }
    }

    public function createVa(Request $request, Tagihan $tagihan, PaymentService $pay): JsonResponse
    {
        $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

        abort_unless($mhs && $tagihan->mahasiswa_id === $mhs->id, 403);

        try {
            $pembayaran = $pay->createVa($tagihan, [
                'nama' => $request->user()->name,
                'email' => $request->user()->email,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'reference' => $pembayaran->reference,
            'va_number' => $pembayaran->va_number,
            'nominal' => $pembayaran->nominal,
        ], 201);
    }
}
