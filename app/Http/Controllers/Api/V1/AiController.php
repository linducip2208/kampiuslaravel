<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\Ai\AnomaliService;
use App\Services\Ai\AtRiskService;
use App\Services\Ai\ChatbotService;
use App\Services\Ai\EssayGradingService;
use App\Services\Ai\MateriAiService;
use App\Services\Ai\NotulenService;
use App\Services\Ai\RagService;
use App\Services\Ai\RekomendasiMkService;
use App\Services\AiKuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function chat(Request $request, ChatbotService $bot): JsonResponse
    {
        $data = $request->validate(['pesan' => 'required|string|min:2|max:2000']);

        // M-058: kuota harian per user (429 bila habis; pola untuk jenis lain).
        if (! AiKuotaService::boleh($request->user()?->id, 'chat')) {
            return response()->json(['ok' => false, 'message' => 'Kuota AI harian habis. Coba lagi besok.'], 429);
        }

        $jawaban = $bot->ask($request->user()?->id, $data['pesan']);
        AiKuotaService::catat($request->user()?->id, 'chat');

        return response()->json(['data' => ['jawaban' => $jawaban]]);
    }

    public function atRisk(Request $request, AtRiskService $svc): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->hasAnyRole(['superadmin', 'admin_prodi', 'pimpinan', 'dosen'])) {
            return response()->json(['data' => $svc->daftarBerisiko((int) $request->integer('limit', 20) ?: 20)]);
        }

        $mhs = $user ? Mahasiswa::where('user_id', $user->id)->first() : null;

        if (! $mhs) {
            return response()->json(['data' => ['skor' => 0, 'level' => 'rendah', 'faktor' => [], 'rekomendasi' => [], 'ipk' => 0]]);
        }

        return response()->json(['data' => $svc->prediksi($mhs->id)]);
    }

    public function rekomendasi(Request $request, RekomendasiMkService $svc): JsonResponse
    {
        $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

        if (! $mhs) {
            return response()->json(['data' => ['batas_sks' => 15, 'ipk' => 0, 'ulangi' => [], 'saran' => 'Data mahasiswa tidak ditemukan.']]);
        }

        return response()->json(['data' => $svc->rekomendasi($mhs->id)]);
    }

    public function grade(Request $request, EssayGradingService $svc): JsonResponse
    {
        $data = $request->validate([
            'jawaban' => 'required|string|min:10|max:10000',
            'kunci' => 'required|string|min:10|max:10000',
        ]);

        return response()->json(['data' => $svc->nilai($data['jawaban'], $data['kunci'], $request->user()?->id)]);
    }

    /**
     * M-057: tanya jawab RAG knowledge-base (kuota + guardrail di service).
     */
    public function rag(Request $request, RagService $svc): JsonResponse
    {
        $data = $request->validate(['pertanyaan' => 'required|string|min:3|max:2000']);

        if (! AiKuotaService::boleh($request->user()?->id, 'rag')) {
            return response()->json(['ok' => false, 'message' => 'Kuota AI harian habis. Coba lagi besok.'], 429);
        }

        return response()->json(['data' => $svc->jawab($data['pertanyaan'], $request->user()?->id)]);
    }

    public function ringkas(Request $request, MateriAiService $svc): JsonResponse
    {
        $data = $request->validate(['teks' => 'required|string|min:20|max:20000']);

        return response()->json(['data' => [
            'ringkasan' => $svc->ringkas($data['teks'], $request->user()?->id),
            'quiz' => $svc->buatQuiz($data['teks']),
        ]]);
    }

    public function anomali(AnomaliService $svc): JsonResponse
    {
        return response()->json(['data' => $svc->anomaliKeuangan()]);
    }

    public function notulen(Request $request, NotulenService $svc): JsonResponse
    {
        $data = $request->validate(['transkrip' => 'required|string|min:10|max:20000']);

        return response()->json(['data' => $svc->notulen($data['transkrip'], $request->user()?->id)]);
    }
}
