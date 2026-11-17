<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Tagihan;
use App\Services\Payment\MidtransAdapter;
use App\Services\Payment\TripayAdapter;
use App\Services\Payment\XenditAdapter;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Refund sisi gateway + checkStatus QRIS Xendit.
 *
 * Pola fake HTTP mengikuti IntegrasiTest (Http::fake per URL) dan
 * pembuatan data minimal mengikuti HardeningTest.
 */
class GatewayRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Setting::set('pay.server_key', 'test-key');
        Setting::set('pay.base_url', 'https://api.example.test');
    }

    private function tagihan(int $nominal = 1000000): Tagihan
    {
        return Tagihan::create([
            'mahasiswa_id' => Mahasiswa::factory()->create()->id,
            'jenis' => 'UKT',
            'nominal' => $nominal,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);
    }

    public function test_xendit_check_status_qris_completed_menjadi_verified(): void
    {
        $tagihan = $this->tagihan();
        Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'reference' => 'simak-1-qris01',
            'va_number' => 'qr-id-123',
            'qr_payload' => 'QRSTRING123',
            'metode' => 'qris_xendit',
            'nominal' => $tagihan->nominal,
            'status' => 'pending',
        ]);

        Http::fake([
            'api.example.test/qr_codes/*' => Http::response([
                'id' => 'qr-id-123',
                'external_id' => 'simak-1-qris01',
                'status' => 'COMPLETED',
            ], 200),
        ]);

        $this->assertSame('verified', (new XenditAdapter)->checkStatus('simak-1-qris01'));
    }

    public function test_xendit_check_status_ewallet_succeeded_menjadi_verified(): void
    {
        $tagihan = $this->tagihan();
        Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'reference' => 'simak-1-ew01',
            'va_number' => 'charge-id-456',
            'metode' => 'ewallet_xendit-gopay',
            'nominal' => $tagihan->nominal,
            'status' => 'pending',
        ]);

        Http::fake([
            'api.example.test/ewallets/charges/*' => Http::response([
                'id' => 'charge-id-456',
                'reference_id' => 'simak-1-ew01',
                'status' => 'SUCCEEDED',
            ], 200),
        ]);

        $this->assertSame('verified', (new XenditAdapter)->checkStatus('simak-1-ew01'));
    }

    public function test_midtrans_refund_sukses(): void
    {
        Http::fake([
            '*' => Http::response(['status_code' => '200', 'status_message' => 'Refund success'], 200),
        ]);

        $this->assertTrue((new MidtransAdapter)->refund('SIMAK-1-ABCDEF', 1000000));
    }

    public function test_tripay_refund_selalu_false_karena_manual(): void
    {
        // Tanpa Http::fake sekalipun: Tripay tak punya API refund publik.
        $this->assertFalse((new TripayAdapter)->refund('SIMAK-1-ABCDEF', 1000000));
    }

    public function test_refund_gagal_gateway_tidak_melempar_exception(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'gateway error'], 500),
        ]);

        $this->assertFalse((new MidtransAdapter)->refund('SIMAK-1-ABCDEF', 1000000));
        $this->assertFalse((new XenditAdapter)->refund('simak-1-abc123', 1000000));
    }

    public function test_refund_timeout_tidak_melempar_exception(): void
    {
        Http::fake([
            '*' => function (): never {
                throw new \Exception('Connection timeout');
            },
        ]);

        $this->assertFalse((new MidtransAdapter)->refund('SIMAK-1-ABCDEF', 1000000));
        $this->assertFalse((new XenditAdapter)->refund('simak-1-abc123', 1000000));
    }
}
