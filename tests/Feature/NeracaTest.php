<?php

namespace Tests\Feature;

use App\Models\JurnalKeuangan;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeracaTest extends TestCase
{
    use RefreshDatabase;

    public function test_neraca_saldo_merangkum_debet_kredit_per_akun(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('pimpinan');

        JurnalKeuangan::create([
            'tanggal' => today()->toDateString(), 'jenis' => 'penerimaan',
            'akun_debet' => 'Kas/Bank', 'akun_kredit' => 'Piutang UKT',
            'nominal' => 1000000, 'keterangan' => 'uji',
        ]);
        JurnalKeuangan::create([
            'tanggal' => today()->toDateString(), 'jenis' => 'refund',
            'akun_debet' => 'Piutang UKT', 'akun_kredit' => 'Kas/Bank',
            'nominal' => 200000, 'keterangan' => 'uji',
        ]);

        $csv = $this->actingAs($user)->get(route('laporan.export.neraca'))
            ->assertOk()->streamedContent();
        $flat = str_replace(["\r", ' ', '"'], '', $csv);

        $this->assertStringContainsString('Kas/Bank;1000000;200000;800000', $flat);
        $this->assertStringContainsString('PiutangUKT;200000;1000000;-800000', $flat);
    }

    public function test_mahasiswa_ditolak_akses_neraca(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        $this->actingAs($mhs)->get(route('laporan.export.neraca'))->assertForbidden();
    }
}
