<?php

namespace Tests\Feature;

use App\Livewire\Notifikasi\PengumumanKelola;
use App\Models\Pengumuman;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Batch 28: CRUD pengumuman + penerbitan (opsional push topik prodi).
 * Menutup celah: pengumuman ditampilkan di mana-mana tapi tak ada
 * UI penerbitan; permission pengumuman.manage tak dipakai.
 */
class Batch28Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** Tanpa permission → 403 di rute maupun komponen. */
    public function test_tanpa_permission_ditolak(): void
    {
        $mhs = $this->user('mahasiswa');

        $this->actingAs($mhs)->get(route('notifikasi.pengumuman'))->assertForbidden();
        Livewire::actingAs($mhs)->test(PengumumanKelola::class)->assertForbidden();
    }

    /** Simpan draf → terbit → tampil di publik; push topik dilewati jujur. */
    public function test_simpan_terbit_dan_tampil_publik(): void
    {
        $adm = $this->user('superadmin');
        $this->actingAs($adm)->get(route('notifikasi.pengumuman'))->assertOk()->assertSee('Belum ada pengumuman');

        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTQ']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PQ', 'slug' => 'pq']);

        $ui = Livewire::actingAs($adm)->test(PengumumanKelola::class)
            ->set('judul', 'Libur Semester')
            ->set('isi', 'Kampus tutup dua minggu.')
            ->set('target', 'semua')
            ->set('prodi_id', $prodi)
            ->set('pushTopik', true)
            ->call('simpan')
            ->assertHasNoErrors();

        $p = Pengumuman::where('judul', 'Libur Semester')->firstOrFail();
        $this->assertNull($p->published_at);

        $ui->call('terbitkan', $p->id)->assertHasNoErrors();
        // Tanpa kredensial FCM: push dilewati, tapi penerbitan tetap jalan.
        $this->assertStringContainsString('dilewati', (string) $ui->get('message'));
        $this->assertNotNull($p->fresh()->published_at);

        $this->get(route('publik.pengumuman'))->assertOk()->assertSee('Libur Semester');

        $ui->call('tarik', $p->id)->assertHasNoErrors();
        $this->assertNull($p->fresh()->published_at);
    }
}
