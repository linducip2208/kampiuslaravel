<?php

namespace Tests\Feature;

use App\Livewire\Organisasi\Kelola;
use App\Livewire\Pmb\Verifikasi;
use App\Models\Organisasi;
use App\Models\PmbGelombang;
use App\Models\PmbNilaiSeleksi;
use App\Models\PmbPendaftar;
use App\Models\User;
use App\Services\PmbVaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 11 ide bank: kedaluwarsa VA, perangkingan seleksi, SK kepengurusan.
 */
class Batch11Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function pendaftar(string $no, string $status = 'diverifikasi'): PmbPendaftar
    {
        return PmbPendaftar::create([
            'no_daftar' => $no, 'nama' => "Nama {$no}", 'email' => "{$no}@t.id",
            'hp' => '081', 'status' => $status,
        ]);
    }

    /** VA baru punya masa berlaku; kedaluwarsa → terbit ulang + settle ditolak. */
    public function test_va_kedaluwarsa_terbit_ulang_dan_settle_ditolak(): void
    {
        $svc = app(PmbVaService::class);
        $p = $this->pendaftar('PMB-B11-VA');

        $r1 = $svc->issueFor($p);
        $this->assertNotNull($p->fresh()->va_expires_at);

        // Selagi berlaku: dipakai ulang (reference sama).
        $this->assertSame($r1['reference'], $svc->issueFor($p->fresh())['reference']);

        // Lewat tempo: terbit ulang + settle reference lama ditolak.
        $p->update(['va_expires_at' => now()->subDay()]);
        $r2 = $svc->issueFor($p->fresh());
        $this->assertNotSame($r1['reference'], $r2['reference']);
        $this->assertFalse($svc->settleByReference($r1['reference']));

        // Reference baru yang berlaku bisa dilunasi.
        $this->assertTrue($svc->settleByReference($r2['reference']));
        $this->assertSame('lunas', $p->fresh()->bayar_status);
    }

    /** Perangkingan: dalam kuota + ambang → lulus, luar kuota → cadangan. */
    public function test_proses_ranking_mengisi_lulus_cadangan(): void
    {
        $gel = PmbGelombang::create(['nama' => 'Gel B11', 'kuota' => 1, 'biaya' => 0, 'aktif' => true]);

        $a = $this->pendaftar('PMB-B11-A');
        $b = $this->pendaftar('PMB-B11-B');
        $a->update(['gelombang_id' => $gel->id]);
        $b->update(['gelombang_id' => $gel->id]);

        foreach ([$a->id => 90.0, $b->id => 80.0] as $pid => $nilai) {
            PmbNilaiSeleksi::create(['pendaftar_id' => $pid, 'jenis' => 'cbt', 'nilai' => $nilai]);
        }

        Livewire::actingAs($this->user('superadmin'))->test(Verifikasi::class)
            ->set('filterGelombang', $gel->id)
            ->set('ambang', 60.0)
            ->call('prosesRanking')
            ->assertHasNoErrors();

        $this->assertSame('lulus', $a->fresh()->status);
        $this->assertSame('cadangan', $b->fresh()->status);
    }

    /** Organisasi baru otomatis dapat nomor SK + tampil di daftar. */
    public function test_organisasi_otomatis_dapat_nomor_sk(): void
    {
        $html = Livewire::actingAs($this->user('superadmin'))->test(Kelola::class)
            ->set('nama', 'UKM Robotik B11')
            ->set('jenis', 'ukm')
            ->call('simpan')
            ->assertHasNoErrors()
            ->html();

        $org = Organisasi::where('nama', 'UKM Robotik B11')->firstOrFail();
        $this->assertNotNull($org->nomor_sk);
        $this->assertStringStartsWith('SK-ORG/', $org->nomor_sk);
        $this->assertStringContainsString($org->nomor_sk, $html);
    }
}
