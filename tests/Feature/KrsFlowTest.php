<?php

namespace Tests\Feature;

use App\Livewire\Krs\Pengajuan;
use App\Livewire\Krs\PersetujuanPa;
use App\Models\Krs;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class KrsFlowTest extends TestCase
{
    use RefreshDatabase;

    private int $mahasiswaId;

    private int $tahunId;

    /** @var list<int> */
    private array $kelasIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dosen');
        $this->actingAs($user);

        // Baris prasyarat modul M1 dibuat langsung via query builder
        // agar test tidak bergantung pada model modul lain.
        $this->tahunId = DB::table('tahun_akademik')->insertGetId([
            'tahun' => '2026',
            'semester' => 'ganjil',
            'aktif' => true,
        ]);

        $fakultasId = DB::table('fakultas')->insertGetId([
            'nama' => 'Teknik',
            'kode' => 'FT',
        ]);

        $prodiId = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fakultasId,
            'nama' => 'Informatika',
            'jenjang' => 'S1',
            'kode' => 'IF',
            'slug' => 'informatika',
        ]);

        $this->mahasiswaId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id,
            'nim' => '20260001',
            'prodi_id' => $prodiId,
            'angkatan' => 2026,
            'status' => 'aktif',
        ]);

        foreach ([['IF101', 3], ['IF102', 3], ['IF103', 2], ['IF104', 3], ['IF105', 3], ['IF106', 4]] as [$kode, $sks]) {
            $mkId = DB::table('matakuliah')->insertGetId([
                'prodi_id' => $prodiId,
                'kode' => $kode,
                'nama' => 'MK '.$kode,
                'sks' => $sks,
                'semester' => 1,
            ]);

            $this->kelasIds[] = DB::table('kelas')->insertGetId([
                'matakuliah_id' => $mkId,
                'tahun_akademik_id' => $this->tahunId,
                'nama' => 'A',
                'kuota' => 40,
            ]);
        }
    }

    public function test_mahasiswa_dapat_mengajukan_krs(): void
    {
        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 3.50)
            ->call('toggleKelas', $this->kelasIds[0])
            ->call('toggleKelas', $this->kelasIds[1])
            ->call('ajukan')
            ->assertHasNoErrors();

        $krs = Krs::query()
            ->where('mahasiswa_id', $this->mahasiswaId)
            ->where('tahun_akademik_id', $this->tahunId)
            ->first();

        $this->assertNotNull($krs);
        $this->assertSame(Krs::STATUS_DIAJUKAN, $krs->status);
        $this->assertSame(6, $krs->total_sks);
        $this->assertCount(2, $krs->details);
    }

    public function test_krs_melebihi_sks_maks_ditolak(): void
    {
        // Total 6 kelas = 18 SKS > batas 15 SKS untuk IPK 1.50.
        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 1.50) // batas 15 SKS
            ->set('kelasDipilih', $this->kelasIds)
            ->call('ajukan')
            ->assertHasErrors(['kelasDipilih']);

        $this->assertNull(
            Krs::query()->where('mahasiswa_id', $this->mahasiswaId)->first()
        );
    }

    public function test_krs_diblokir_saat_ada_tunggakan(): void
    {
        // Tabel tagihan milik modul keuangan (singular `tagihan`).
        $tabel = Schema::hasTable('tagihan') ? 'tagihan' : (Schema::hasTable('tagihans') ? 'tagihans' : null);

        if (! $tabel) {
            $this->markTestSkipped('Tabel tagihan belum tersedia.');
        }

        DB::table($tabel)->insert([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'jenis' => 'UKT',
            'nominal' => 5000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);

        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 3.80)
            ->call('toggleKelas', $this->kelasIds[0])
            ->call('ajukan')
            ->assertHasErrors(['kelasDipilih']);

        $this->assertNull(
            Krs::query()->where('mahasiswa_id', $this->mahasiswaId)->first()
        );
    }

    public function test_dosen_pa_dapat_menyetujui_dan_mencetak_krs(): void
    {
        $krs = Krs::factory()->create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'status' => Krs::STATUS_DIAJUKAN,
            'total_sks' => 6,
        ]);

        foreach (array_slice($this->kelasIds, 0, 2) as $kelasId) {
            $krs->details()->create(['kelas_id' => $kelasId, 'sks' => 3, 'status' => 'aktif']);
        }

        Livewire::test(PersetujuanPa::class)
            ->call('setujui', $krs->id);

        $this->assertSame(Krs::STATUS_DISETUJUI, $krs->fresh()->status);

        $this->get(route('krs.cetak', $krs))->assertOk()->assertSee((string) $krs->id);
    }
}
