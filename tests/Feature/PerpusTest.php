<?php

namespace Tests\Feature;

use App\Livewire\Perpus\Sirkulasi;
use App\Models\PerpusBuku;
use App\Models\PerpusEksemplar;
use App\Models\PerpusPinjam;
use App\Models\User;
use App\Services\PerpustakaanService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Perpustakaan batch 6: sirkulasi pinjam/kembali + denda, batas 3,
 * katalog publik, peran pustakawan.
 */
class PerpusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function bukuDenganEksemplar(string $kode, int $jumlah = 1): PerpusBuku
    {
        $buku = PerpusBuku::create(['kode' => $kode, 'judul' => 'Buku '.$kode, 'pengarang' => 'Pengarang']);

        for ($i = 1; $i <= $jumlah; $i++) {
            PerpusEksemplar::create(['buku_id' => $buku->id, 'barcode' => $kode.'-00'.$i]);
        }

        return $buku->fresh();
    }

    public function test_pinjam_dan_kembali_tepat_waktu(): void
    {
        $buku = $this->bukuDenganEksemplar('BK-1');
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');

        $pinjam = PerpustakaanService::pinjam($buku->eksemplar()->first()->id, $user->id);
        $this->assertSame('dipinjam', PerpusEksemplar::find($pinjam->eksemplar_id)->status);
        $this->assertSame(now()->addDays(14)->toDateString(), (string) $pinjam->jatuh_tempo->toDateString());

        $selesai = PerpustakaanService::kembali($pinjam->id);
        $this->assertSame('kembali', $selesai->status);
        $this->assertSame(0, (int) $selesai->denda_rp);
        $this->assertSame('tersedia', PerpusEksemplar::find($pinjam->eksemplar_id)->status);
    }

    public function test_batas_tiga_pinjaman_aktif(): void
    {
        $buku = $this->bukuDenganEksemplar('BK-2', 4);
        $user = User::factory()->create();

        foreach ($buku->eksemplar()->take(3)->pluck('id')->all() as $eksId) {
            PerpustakaanService::pinjam($eksId, $user->id);
        }

        $this->expectException(\RuntimeException::class);
        PerpustakaanService::pinjam($buku->eksemplar()->latest('id')->first()->id, $user->id);
    }

    public function test_denda_keterlambatan_dan_hilang(): void
    {
        $buku = $this->bukuDenganEksemplar('BK-3', 2);
        $user = User::factory()->create();
        $eksIds = $buku->eksemplar()->pluck('id')->all();

        $telat = PerpusPinjam::create([
            'eksemplar_id' => $eksIds[0], 'user_id' => $user->id,
            'tgl_pinjam' => now()->subDays(20)->toDateString(),
            'jatuh_tempo' => now()->subDays(6)->toDateString(),
            'status' => 'pinjam',
        ]);
        PerpusEksemplar::whereKey($eksIds[0])->update(['status' => 'dipinjam']);

        $selesai = PerpustakaanService::kembali($telat->id);
        $this->assertSame(6000, (int) $selesai->denda_rp);

        $hilang = PerpusPinjam::create([
            'eksemplar_id' => $eksIds[1], 'user_id' => $user->id,
            'tgl_pinjam' => now()->subDay()->toDateString(),
            'jatuh_tempo' => now()->addDays(13)->toDateString(),
            'status' => 'pinjam',
        ]);
        PerpusEksemplar::whereKey($eksIds[1])->update(['status' => 'dipinjam']);

        $selesaiHilang = PerpustakaanService::kembali($hilang->id, 'hilang');
        $this->assertSame('hilang', $selesaiHilang->status);
        $this->assertSame('hilang', PerpusEksemplar::find($eksIds[1])->status);
    }

    public function test_katalog_publik_dan_akses_sirkulasi(): void
    {
        $this->bukuDenganEksemplar('BK-4');

        $this->get(route('publik.katalog'))->assertOk()->assertSee('BK-4');

        $pus = User::factory()->create();
        $pus->assignRole('pustakawan');
        $this->actingAs($pus)->get(route('perpus.sirkulasi'))->assertOk();

        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');
        $this->actingAs($mhs)->get(route('perpus.sirkulasi'))->assertForbidden();
    }

    public function test_livewire_sirkulasi_pinjam_lewat_barcode(): void
    {
        $this->bukuDenganEksemplar('BK-5');
        $adm = User::factory()->create();
        $adm->assignRole('superadmin');
        $anggota = User::factory()->create();

        Livewire::actingAs($adm)->test(Sirkulasi::class)
            ->set('barcode', 'BK-5-001')
            ->set('userId', $anggota->id)
            ->call('pinjam')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('perpus_pinjam', ['user_id' => $anggota->id, 'status' => 'pinjam']);
        $this->assertSame('dipinjam', PerpusEksemplar::where('barcode', 'BK-5-001')->value('status'));
    }
}
