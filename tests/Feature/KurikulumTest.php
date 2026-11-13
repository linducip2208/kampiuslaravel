<?php

namespace Tests\Feature;

use App\Livewire\Master\Kurikulum as KurikulumLivewire;
use App\Models\Kurikulum;
use App\Models\Matakuliah;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KurikulumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        $this->actingAs($user);
    }

    public function test_kurikulum_versi_dan_pemetaan_mk(): void
    {
        $prodi = Prodi::factory()->create();
        $mk = Matakuliah::factory()->create(['prodi_id' => $prodi->id]);

        Livewire::test(KurikulumLivewire::class)
            ->set('formKurikulum', [
                'prodi_id' => $prodi->id,
                'kode' => 'KUR2024',
                'nama' => 'Kurikulum 2024',
                'tahun_berlaku' => 2024,
                'status' => 'aktif',
                'keterangan' => null,
            ])
            ->call('simpanKurikulum')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kurikulum', ['kode' => 'KUR2024', 'prodi_id' => $prodi->id]);

        $kurikulumId = Kurikulum::where('kode', 'KUR2024')->value('id');

        Livewire::test(KurikulumLivewire::class)
            ->set('kurikulumId', $kurikulumId)
            ->set('formMk', ['matakuliah_id' => $mk->id, 'semester' => 2, 'sifat' => 'wajib'])
            ->call('tambahMk')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kurikulum_mk', [
            'kurikulum_id' => $kurikulumId,
            'matakuliah_id' => $mk->id,
            'semester' => 2,
        ]);
    }

    public function test_prasyarat_ekivalensi_dan_tolak_siklus(): void
    {
        $prodi = Prodi::factory()->create();
        $a = Matakuliah::factory()->create(['prodi_id' => $prodi->id]);
        $b = Matakuliah::factory()->create(['prodi_id' => $prodi->id]);

        Livewire::test(KurikulumLivewire::class)
            ->set('formRelasi', ['matakuliah_id' => $b->id, 'prasyarat_id' => $a->id, 'jenis' => 'prasyarat', 'keterangan' => null])
            ->call('tambahRelasi')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mk_prasyarat', [
            'matakuliah_id' => $b->id, 'prasyarat_id' => $a->id, 'jenis' => 'prasyarat',
        ]);

        // Legacy KRS tetap terisi.
        $this->assertSame($a->id, $b->fresh()->prasyarat_id);

        // Ekivalensi lintas versi kurikulum.
        Livewire::test(KurikulumLivewire::class)
            ->set('formRelasi', ['matakuliah_id' => $a->id, 'prasyarat_id' => $b->id, 'jenis' => 'ekivalensi', 'keterangan' => 'Konversi KUR2020'])
            ->call('tambahRelasi')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mk_prasyarat', [
            'matakuliah_id' => $a->id, 'prasyarat_id' => $b->id, 'jenis' => 'ekivalensi',
        ]);

        // Siklus prasyarat B->A lalu A->B harus ditolak.
        Livewire::test(KurikulumLivewire::class)
            ->set('formRelasi', ['matakuliah_id' => $a->id, 'prasyarat_id' => $b->id, 'jenis' => 'prasyarat', 'keterangan' => null])
            ->call('tambahRelasi')
            ->assertHasErrors(['formRelasi.prasyarat_id']);
    }

    public function test_route_admin_kurikulum_memerlukan_peran(): void
    {
        $this->get(route('admin.kurikulum'))->assertOk()->assertSee('Kurikulum', false);

        $biasa = User::factory()->create();
        $this->actingAs($biasa);
        $this->get(route('admin.kurikulum'))->assertForbidden();
    }
}
