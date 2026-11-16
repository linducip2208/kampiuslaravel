<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\Payroll;
use App\Livewire\Rekrutmen\Kelola;
use App\Models\LamaranKerja;
use App\Models\LowonganKerja;
use App\Models\OnboardingChecklist;
use App\Models\TunjanganTemplate;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 18 ide bank: tunjangan template per peran, onboarding checklist,
 * snapshot offline KHS.
 */
class Batch18Test extends TestCase
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

    /** Template tunjangan terisi otomatis saat pegawai dipilih. */
    public function test_tunjangan_template_terisi_otomatis(): void
    {
        $dosen = $this->user('dosen');
        TunjanganTemplate::create(['role' => 'dosen', 'nominal' => 750000]);

        $ui = Livewire::actingAs($this->user('superadmin'))->test(Payroll::class)
            ->set('user_id', $dosen->id);

        $this->assertSame(750000, $ui->get('tunjangan'));
        $this->assertStringContainsString('template peran dosen', (string) $ui->get('message'));

        Livewire::actingAs($this->user('superadmin'))->test(Payroll::class)
            ->set('templateRole', 'tendik')
            ->set('templateNominal', 500000)
            ->call('simpanTemplate')
            ->assertHasNoErrors();

        $this->assertSame(500000, (int) TunjanganTemplate::where('role', 'tendik')->value('nominal'));
    }

    /** Lamaran diterima memunculkan checklist; bisa dicentang. */
    public function test_onboarding_checklist_per_lamaran_diterima(): void
    {
        $lowongan = LowonganKerja::create(['judul' => 'Dosen B18', 'jenis' => 'dosen', 'kuota' => 1, 'status' => 'buka']);
        $lamaran = LamaranKerja::create([
            'lowongan_id' => $lowongan->id, 'nama' => 'Pelamar B18',
            'email' => 'b18@t.id', 'hp' => '081', 'status' => 'masuk',
        ]);

        $ui = Livewire::actingAs($this->user('superadmin'))->test(Kelola::class)
            ->set('lihatLowonganId', $lowongan->id)
            ->call('ubahStatusLamaran', $lamaran->id, 'diterima')
            ->assertHasNoErrors();

        $this->assertSame('diterima', $lamaran->fresh()->status);
        $this->assertSame(3, OnboardingChecklist::where('lamaran_id', $lamaran->id)->count());
        $this->assertStringContainsString('Buat akun pengguna', $ui->html());

        $item = OnboardingChecklist::where('lamaran_id', $lamaran->id)->firstOrFail();
        $ui->call('toggleOnboarding', $item->id);
        $this->assertTrue($item->fresh()->selesai);
    }

    /** Halaman KHS menanam snapshot offline (localStorage + banner). */
    public function test_khs_menanam_snapshot_offline(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTI']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PI', 'slug' => 'pi']);
        $u = $this->user('mahasiswa');
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20249993', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);

        $this->actingAs($u)->get(route('khs.show', $mhsId))
            ->assertOk()
            ->assertSee('khs-cache-'.$mhsId)
            ->assertSee('localStorage')
            ->assertSee('khs-offline-banner');
    }
}
