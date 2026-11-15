<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Landing page ala konsep visual: 7 perubahan utama + tanpa klaim palsu.
 */
class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_memuat_tujuh_elemen_kunci(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        assert(is_string($html));

        // 1. Hero kuat + dashboard ERP dominan (mockup + angka real).
        $this->assertStringContainsString('Satu Platform Terintegrasi', $html);
        $this->assertStringContainsString('Pratinjau dashboard KAMPUS ERP', $html);
        $this->assertStringContainsString('Lihat Demo Live', $html);

        // 2. Kepercayaan institusi tampil awal (kategori generik, bukan logo palsu).
        $this->assertStringContainsString('Perguruan Tinggi', $html);

        // 3. Alur PMB → Akademik → Keuangan → Wisuda → Alumni.
        foreach (['PMB', 'Akademik', 'Keuangan', 'Wisuda'] as $tahap) {
            $this->assertStringContainsString($tahap, $html);
        }

        // 4. Grid modul modern + tautan modul asli.
        $this->assertStringContainsString('Fitur Lengkap untuk Semua Kebutuhan Kampus', $html);
        $this->assertStringContainsString(route('pmb.create'), $html);

        // 5. Statistik + testimoni (berlabel ilustrasi, angka real DB).
        $this->assertStringContainsString('Tingkat Pelunasan', $html);
        $this->assertStringContainsString('ilustrasi', $html);

        // 6. CTA Request Demo jelas.
        $this->assertStringContainsString('Request Demo', $html);
        $this->assertStringContainsString('Siap Meningkatkan Layanan Kampus Anda?', $html);

        // 7. Footer profesional + tombol kembali ke atas.
        $this->assertStringContainsString('KAMPUS ERP', $html);
        $this->assertStringContainsString('id="toTop"', $html);
    }

    public function test_landing_tanpa_klaim_palsu(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        assert(is_string($html));

        // Tanpa nama universitas fiktif sebagai klien, tanpa uptime palsu.
        foreach (['Universitas Indonesia', 'Gadjah Mada', '99.9%', '1.000.000+', '500+'] as $klaim) {
            $this->assertStringNotContainsString($klaim, $html);
        }
    }

    public function test_landing_angka_mengikuti_database(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('mahasiswa');
        \DB::table('mahasiswa')->insert([
            'user_id' => $u->id, 'nim' => '20209999', 'prodi_id' => \DB::table('prodi')->insertGetId([
                'fakultas_id' => \DB::table('fakultas')->insertGetId(['nama' => 'F', 'kode' => 'FF']),
                'nama' => 'P', 'jenjang' => 'S1', 'kode' => 'PP', 'slug' => 'pp',
            ]), 'angkatan' => 2026, 'status' => 'aktif',
        ]);

        $this->get('/')->assertOk()->assertSee('>1<', false);
    }

    public function test_nav_publik_memuat_jangkar_dan_demo(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        assert(is_string($html));

        foreach (['/#fitur', '/#modul', '/#keamanan', '/#faq', 'Request Demo'] as $fragmen) {
            $this->assertStringContainsString($fragmen, $html);
        }
    }
}
