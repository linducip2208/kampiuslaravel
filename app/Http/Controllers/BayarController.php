<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\KalenderService;
use App\Services\Payment\PaymentService;
use App\Services\PmbVaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BayarController extends Controller
{
    use AuthorizesRequests;

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

    public function createVa(Request $request, Tagihan $tagihan, PaymentService $pay): JsonResponse
    {
        $this->authorize('bayar', $tagihan);

        abort_if($tagihan->status === 'lunas', 422, 'Tagihan sudah lunas.');

        if (! KalenderService::isOpen('pembayaran')) {
            return response()->json(['message' => KalenderService::closedMessage('pembayaran')], 422);
        }

        $data = $request->validate([
            'method' => 'nullable|string|max:30',
        ]);
        $channel = strtolower((string) ($data['method'] ?? 'va'));

        try {
            $mahasiswa = $tagihan->mahasiswa;
            $customer = [
                'nama' => $mahasiswa->user->name ?? 'Mahasiswa',
                'email' => $mahasiswa->user->email ?? null,
                'method' => $data['method'] ?? null,
            ];

            $pembayaran = match (PaymentService::channelGroup($channel)) {
                'qris' => $pay->createQris($tagihan, $customer),
                'ewallet' => $pay->createEwallet($tagihan, $customer, $channel),
                default => $pay->createVa($tagihan, $customer),
            };
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            report(new \RuntimeException('Gagal menghubungi payment gateway.'));

            return response()->json(['message' => 'Gateway pembayaran tidak dapat dihubungi. Coba lagi nanti.'], 502);
        }

        return response()->json([
            'reference' => $pembayaran->reference,
            'va_number' => $pembayaran->va_number,
            'qr_payload' => $pembayaran->qr_payload,
            'expires_at' => $pembayaran->expires_at,
            'metode' => $pembayaran->metode,
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

        // Fallback PMBVA HANYA untuk driver simulasi (tanpa signature by design,
        // sudah ditolak di production pada guard di atas). Di driver nyata,
        // callback tanpa signature valid DITOLAK — jika tidak, siapa pun bisa
        // melunasi VA hanya dengan menebak reference.
        if (! $reference && $pay->activeDriverKey() === 'simulated' && str_starts_with((string) ($payload['reference'] ?? ''), 'PMBVA-')) {
            $reference = (string) $payload['reference'];
        }

        if (! $reference) {
            return response()->json(['ok' => false], 400);
        }

        // VA biaya pendaftaran PMB (bukan tagihan mahasiswa): lunasi pendaftar.
        if (str_starts_with($reference, 'PMBVA-')) {
            return response()->json(['ok' => app(PmbVaService::class)->settleByReference($reference)]);
        }

        // Tolak nominal callback yang tidak cocok dengan tagihan (anti manipulasi).
        $amount = $payload['amount'] ?? $payload['total_amount'] ?? $payload['gross_amount'] ?? $payload['expected_amount'] ?? null;
        if ($amount !== null) {
            $pembayaran = Pembayaran::where('reference', $reference)->first();
            if ($pembayaran && (int) $amount !== (int) $pembayaran->nominal) {
                return response()->json(['ok' => false, 'message' => 'Nominal tidak cocok.'], 400);
            }
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

    /** Refund pembayaran verified (role keuangan). Reversal terbayar + jurnal. */
    public function refund(Request $request, Pembayaran $pembayaran, PaymentService $pay): JsonResponse
    {
        $this->authorize('refund', $pembayaran);

        $data = $request->validate(['reason' => 'nullable|string|max:255']);

        $ok = $pay->refundByReference(
            (string) $pembayaran->reference,
            (int) Auth::id(),
            $data['reason'] ?? null
        );

        return response()->json(['ok' => $ok], $ok ? 200 : 422);
    }

    private function authorizeTagihan(Tagihan $tagihan): void
    {
        $this->authorize('view', $tagihan);
    }

    private function authorizePembayaran(Pembayaran $pembayaran): void
    {
        $this->authorize('view', $pembayaran);
    }
}
