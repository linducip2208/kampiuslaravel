<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BayarController extends Controller
{
    public function index(): View
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');

        $tagihan = $mahasiswaId
            ? Tagihan::with('tahunAkademik')->where('mahasiswa_id', $mahasiswaId)->latest()->get()
            : collect();

        $pending = $mahasiswaId
            ? Pembayaran::whereIn('tagihan_id', $tagihan->pluck('id'))->where('status', 'pending')->latest()->get()
            : collect();

        return view('keuangan.bayar', compact('tagihan', 'pending'));
    }

    public function createVa(Tagihan $tagihan, PaymentService $pay): JsonResponse
    {
        $this->authorizeTagihan($tagihan);

        try {
            $mahasiswa = $tagihan->mahasiswa;
            $pembayaran = $pay->createVa($tagihan, [
                'nama' => $mahasiswa->user->name ?? 'Mahasiswa',
                'email' => $mahasiswa->user->email ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            report(new \RuntimeException('Gagal menghubungi payment gateway.'));

            return response()->json(['message' => 'Gateway pembayaran tidak dapat dihubungi. Coba lagi nanti.'], 502);
        }

        return response()->json([
            'reference' => $pembayaran->reference,
            'va_number' => $pembayaran->va_number,
            'nominal' => $pembayaran->nominal,
        ]);
    }

    public function callback(Request $request, PaymentService $pay): JsonResponse
    {
        // Driver simulasi tidak punya signature: tolak di production.
        if (app()->isProduction() && $pay->activeDriverKey() === 'simulated') {
            return response()->json(['ok' => false, 'message' => 'Driver simulasi nonaktif di production.'], 403);
        }
        // Header signature diteruskan ke adapter (Tripay pakai HMAC body).
        $payload = $request->all() + [
            '_signature' => $request->header('X-Callback-Signature'),
            '_event' => $request->header('X-Callback-Event'),
            '_raw' => $request->getContent(),
        ];

        $reference = $pay->driver()->verifyCallback($payload);

        if (! $reference) {
            return response()->json(['ok' => false], 400);
        }

        return response()->json(['ok' => $pay->settleByReference($reference)]);
    }

    public function status(Pembayaran $pembayaran, PaymentService $pay): JsonResponse
    {
        $this->authorizePembayaran($pembayaran);

        return response()->json([
            'reference' => $pembayaran->reference,
            'status' => $pay->syncStatus($pembayaran),
        ]);
    }

    private function authorizeTagihan(Tagihan $tagihan): void
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');

        abort_unless($tagihan->mahasiswa_id === $mahasiswaId || Auth::user()->hasRole('keuangan'), 403);
    }

    private function authorizePembayaran(Pembayaran $pembayaran): void
    {
        $this->authorizeTagihan($pembayaran->tagihan);
    }
}
