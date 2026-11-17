<?php

namespace Tests\Feature;

use App\Livewire\Admin\Akreditasi as AkreditasiLivewire;
use App\Livewire\Admin\Tracer as TracerLivewire;
use App\Livewire\Asrama\Kelola as AsramaKelolaLivewire;
use App\Models\AkreditasiDokumen;
use App\Models\AsramaHuni;
use App\Models\AsramaKamar;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\SpmiSiklus;
use App\Models\TracerAlumni;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Budget query batch 26: komponen ide 6-26 wajib eager-load relasi yang
 * di-loop di blade (with/withCount), sehingga render paginasi 10 baris
 * tidak meledak menjadi N+1. Ambang longgar tapi mengikat: gagal bila
 * N+1 parah (mis. query per baris tanpa eager).
 */
class PerfBatch26Test extends TestCase
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

    private function countRenderQueries(callable $render): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $render();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    }

    /** Kelola asrama: 3 kamar + 3 penghuni, render < 15 query. */
    public function test_asrama_kelola_render_terbatas(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $kamar = AsramaKamar::create([
                'gedung' => 'Gedung A', 'kode' => 'A-10'.$i,
                'kapasitas' => 2, 'iuran' => 500000,
            ]);
            $mhs = Mahasiswa::factory()->create();
            AsramaHuni::create([
                'kamar_id' => $kamar->id, 'mahasiswa_id' => $mhs->id,
                'masuk_at' => today()->toDateString(),
            ]);
        }

        $total = $this->countRenderQueries(
            fn () => Livewire::test(AsramaKelolaLivewire::class)->assertOk()
        );

        $this->assertLessThan(15, $total, "Render Kelola asrama memicu {$total} query (N+1?).");
    }

    /** Akreditasi: dokumen + siklus prodi, render < 20 query. */
    public function test_akreditasi_render_terbatas(): void
    {
        $prodi = Prodi::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            AkreditasiDokumen::create([
                'prodi_id' => $prodi->id, 'kategori' => 'borang-'.$i,
                'judul' => 'Dokumen '.$i, 'status' => 'draft',
            ]);
            SpmiSiklus::create([
                'prodi_id' => $prodi->id, 'tahun' => 2020 + $i,
                'tahap' => SpmiSiklus::TAHAP[($i - 1) % count(SpmiSiklus::TAHAP)],
                'status' => 'sedang',
            ]);
        }

        $total = $this->countRenderQueries(
            fn () => Livewire::test(AkreditasiLivewire::class)->assertOk()
        );

        $this->assertLessThan(20, $total, "Render Akreditasi memicu {$total} query (N+1?).");
    }

    /** Tracer: 5 responden + agregat ringkasan/tren, render < 20 query. */
    public function test_tracer_render_terbatas(): void
    {
        $statuses = ['bekerja', 'wirausaha', 'kuliah_lanjut', 'mencari_kerja', 'lainnya'];

        foreach ($statuses as $i => $status) {
            TracerAlumni::create([
                'mahasiswa_id' => Mahasiswa::factory()->create()->id,
                'tahun_lulus' => 2023 + ($i % 2),
                'status_kerja' => $status,
            ]);
        }

        $total = $this->countRenderQueries(
            fn () => Livewire::test(TracerLivewire::class)->assertOk()
        );

        $this->assertLessThan(20, $total, "Render Tracer memicu {$total} query (N+1?).");
    }
}
