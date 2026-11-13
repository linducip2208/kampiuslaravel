<?php

namespace Tests\Feature;

use App\Models\JurnalKeuangan;
use App\Models\Mahasiswa;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Services\KeuanganService;
use App\Services\Payment\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeuanganPhase2Test extends TestCase
{
    use RefreshDatabase;

    private function tagihan(int $nominal = 1000000, array $extra = []): Tagihan
    {
        $mhs = Mahasiswa::factory()->create();

        return Tagihan::create(array_merge([
            'mahasiswa_id' => $mhs->id,
            'tahun_akademik_id' => TahunAkademik::factory()->create()->id,
            'jenis' => 'UKT',
            'nominal' => $nominal,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ], $extra));
    }

    public function test_qris_simulated_menyimpan_qr_payload(): void
    {
        Setting::set('pay.driver', 'simulated');

        $pembayaran = app(PaymentService::class)->createQris($this->tagihan(), ['nama' => 'Tes']);

        $this->assertSame('qris_simulated', $pembayaran->metode);
        $this->assertNotNull($pembayaran->qr_payload);
    }

    public function test_ewallet_simulated_menyimpan_metode_wallet(): void
    {
        Setting::set('pay.driver', 'simulated');

        $pembayaran = app(PaymentService::class)->createEwallet($this->tagihan(), ['nama' => 'Tes'], 'gopay');

        $this->assertSame('ewallet_simulated-gopay', $pembayaran->metode);
        $this->assertNotNull($pembayaran->qr_payload);
    }

    public function test_reuse_pending_kanal_sama_tanpa_dobel(): void
    {
        Setting::set('pay.driver', 'simulated');
        $tagihan = $this->tagihan();

        $a = app(PaymentService::class)->createVa($tagihan, []);
        $b = app(PaymentService::class)->createVa($tagihan, []);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Pembayaran::where('tagihan_id', $tagihan->id)->count());
    }

    public function test_settle_mencatat_jurnal_penerimaan_idempoten(): void
    {
        Setting::set('pay.driver', 'simulated');
        $pay = app(PaymentService::class);
        $pembayaran = $pay->createVa($this->tagihan(), []);

        $this->assertTrue($pay->settleByReference($pembayaran->reference));
        $this->assertFalse($pay->settleByReference($pembayaran->reference));

        $this->assertDatabaseHas('jurnal_keuangan', [
            'pembayaran_id' => $pembayaran->id,
            'jenis' => 'penerimaan',
            'akun_debet' => 'Kas/Bank',
            'nominal' => $pembayaran->nominal,
        ]);
        $this->assertSame(1, JurnalKeuangan::where('pembayaran_id', $pembayaran->id)->count());
    }

    public function test_verify_manual_mencatat_jurnal(): void
    {
        $bayar = Pembayaran::create([
            'tagihan_id' => $this->tagihan()->id, 'metode' => 'manual',
            'nominal' => 1000000, 'status' => 'pending',
        ]);

        KeuanganService::verifyPembayaran($bayar->id, 1, true);

        $this->assertDatabaseHas('jurnal_keuangan', ['pembayaran_id' => $bayar->id, 'jenis' => 'penerimaan']);
    }

    public function test_refund_mengurangi_terbayar_dan_mencatat_reversal(): void
    {
        $tagihan = $this->tagihan();
        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'manual',
            'nominal' => 1000000, 'status' => 'pending',
        ]);
        KeuanganService::verifyPembayaran($bayar->id, 1, true);
        $this->assertSame('lunas', $tagihan->fresh()->status);

        $this->assertTrue(KeuanganService::refundPembayaran($bayar->id, 1, 'Dobel bayar'));
        $this->assertFalse(KeuanganService::refundPembayaran($bayar->id, 1));

        $this->assertSame('belum_bayar', $tagihan->fresh()->status);
        $this->assertSame(0, (int) $tagihan->fresh()->terbayar);
        $this->assertNotNull($bayar->fresh()->refunded_at);
        $this->assertDatabaseHas('jurnal_keuangan', [
            'pembayaran_id' => $bayar->id, 'jenis' => 'refund', 'akun_kredit' => 'Kas/Bank',
        ]);
    }

    public function test_rekonsiliasi_csv(): void
    {
        Setting::set('pay.driver', 'simulated');
        $pay = app(PaymentService::class);
        $a = $pay->createVa($this->tagihan(500000), []);
        $b = $pay->createVa($this->tagihan(750000), []);

        $csv = "reference,nominal\n{$a->reference},500000\n{$b->reference},700000\nSIM-TIDAK-ADA,1000\n";
        $hasil = KeuanganService::rekonsiliasiCsv($csv);

        $this->assertSame(1, $hasil['matched']);
        $this->assertCount(1, $hasil['selisih']);
        $this->assertSame($b->reference, $hasil['selisih'][0]['reference']);
        $this->assertSame(['SIM-TIDAK-ADA'], $hasil['tak_dikenal']);
        $this->assertNotNull($a->fresh()->reconciled_at);
        $this->assertNull($b->fresh()->reconciled_at);
    }

    public function test_callback_menolak_nominal_tidak_cocok(): void
    {
        Setting::set('pay.driver', 'simulated');
        $pembayaran = app(PaymentService::class)->createVa($this->tagihan(1000000), []);

        $this->postJson(route('payment.callback'), [
            'status' => 'paid',
            'reference' => $pembayaran->reference,
            'amount' => 500000,
        ])->assertStatus(400);

        $this->assertSame('pending', $pembayaran->fresh()->status);
    }

    public function test_reminder_h7_menandai_dan_membuat_notifikasi(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $tagihan = $this->tagihan(1000000, ['jatuh_tempo' => today()->addDays(7)->toDateString()]);

        $this->artisan('tagihan:ingatkan', ['--arah' => 'h-7'])->assertSuccessful();

        $this->assertNotNull($tagihan->fresh()->notif_h7_at);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $tagihan->mahasiswa->user_id,
            'judul' => 'Tagihan jatuh tempo 7 hari lagi',
        ]);

        // Idempoten: jalan kedua tidak membuat notifikasi baru.
        $this->artisan('tagihan:ingatkan', ['--arah' => 'h-7'])->assertSuccessful();
        $this->assertSame(1, Notifikasi::where('user_id', $tagihan->mahasiswa->user_id)->count());
    }

    public function test_kwitansi_menampilkan_rincian_pajak(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        Setting::set('pajak.ppn_persen', '11');
        Setting::set('pajak.npwp', '01.234.567.8-901.000');

        $tagihan = $this->tagihan(1110000);
        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'qris_simulated',
            'nominal' => 1110000, 'status' => 'pending',
        ]);
        KeuanganService::verifyPembayaran($bayar->id, 1, true);

        $owner = $tagihan->mahasiswa->user;
        $this->actingAs($owner)->get(route('kwitansi.cetak', $bayar->id))
            ->assertOk()
            ->assertSee('Rincian Pajak')
            ->assertSee('01.234.567.8-901.000');
    }
}
