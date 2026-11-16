<?php

namespace Tests\Feature;

use App\Livewire\Klinik\Kunjungan;
use App\Models\AuditBaca;
use App\Models\AuditLog;
use App\Models\KlinikKunjungan;
use App\Models\KlinikResep;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Batch 20 ide bank: kunjungan + resep klinik (gate + audit akses).
 */
class Batch20Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        // Seed ulang tiap test membuat ID permission baru; cache Spatie
        // (array, seumur proses phpunit) harus dibuang agar gate akurat.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** Non-klinik ditolak di rute maupun komponen. */
    public function test_non_klinik_ditolak(): void
    {
        $mhs = $this->user('mahasiswa');

        $this->actingAs($mhs)->get(route('klinik.kunjungan'))->assertForbidden();
        Livewire::actingAs($mhs)->test(Kunjungan::class)->assertForbidden();
    }

    /** Petugas mencatat kunjungan + resep; buka rekam tercatat di audit. */
    public function test_kunjungan_resep_dan_audit_baca(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTK']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PK', 'slug' => 'pk']);
        $this->actingAs($this->user('petugas_klinik'))->get(route('klinik.kunjungan'))->assertOk();

        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $this->user('mahasiswa')->id, 'nim' => '202499K1',
            'prodi_id' => $prodi, 'angkatan' => 2024, 'status' => 'aktif',
        ]);

        $ui = Livewire::actingAs($this->user('petugas_klinik'))->test(Kunjungan::class)
            ->set('nim', '202499K1')
            ->set('keluhan', 'Demam dan batuk')
            ->set('diagnosa', 'ISPA ringan')
            ->call('simpan')
            ->assertHasNoErrors();

        $kunjungan = KlinikKunjungan::where('mahasiswa_id', $mhsId)->firstOrFail();
        $this->assertTrue(AuditLog::where('action', 'klinik.kunjungan')->where('model_id', $kunjungan->id)->exists());

        $ui->call('lihat', $kunjungan->id)
            ->assertSee('ISPA ringan');

        $this->assertTrue(AuditBaca::where('model', 'klinik_kunjungan')->where('model_id', $kunjungan->id)->exists());

        $ui->set('obat', 'Paracetamol')->set('dosis', '3x1')->set('jumlah', '10 tablet')
            ->call('simpanResep')
            ->assertHasNoErrors();

        $this->assertTrue(KlinikResep::where('kunjungan_id', $kunjungan->id)->where('obat', 'Paracetamol')->exists());
    }
}
