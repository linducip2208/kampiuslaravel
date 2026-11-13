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

        foreach ([['IF101', 3], ['IF102', 3], ['IF103', 2], ['IF104', 3], ['IF105', 3], ['IF106', 4], ['IF107', 3]] as [$kode, $sks]) {
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
        // Total 7 kelas = 21 SKS > batas 18 SKS untuk IPK 1.50.
        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 1.50) // batas 18 SKS
            ->set('kelasDipilih', $this->kelasIds)
            ->call('ajukan')
            ->assertHasErrors(['kelasDipilih']);

        $this->assertNull(
            Krs::query()->where('mahasiswa_id', $this->mahasiswaId)->first()
        );
    }

    public function test_batas_sks_by_ipk_24_22_20_18(): void
    {
        $this->assertSame(24, Krs::sksMaksByIpk(4.00));
        $this->assertSame(24, Krs::sksMaksByIpk(3.00));
        $this->assertSame(22, Krs::sksMaksByIpk(2.99));
        $this->assertSame(22, Krs::sksMaksByIpk(2.50));
        $this->assertSame(20, Krs::sksMaksByIpk(2.49));
        $this->assertSame(20, Krs::sksMaksByIpk(2.00));
        $this->assertSame(18, Krs::sksMaksByIpk(1.99));
        $this->assertSame(18, Krs::sksMaksByIpk(0.00));
    }

    public function test_kelas_penuh_diarahkan_ke_waitlist(): void
    {
        // Kelas kuota 1 yang sudah ditempati KRS mahasiswa lain.
        $kelasId = $this->kelasIds[0];
        DB::table('kelas')->where('id', $kelasId)->update(['kuota' => 1]);

        $penghuni = $this->buatMahasiswaLain();
        Krs::factory()->create([
            'mahasiswa_id' => $penghuni,
            'tahun_akademik_id' => $this->tahunId,
            'status' => Krs::STATUS_DIAJUKAN,
            'total_sks' => 3,
            'ipk_snapshot' => 3.50,
        ])->details()->create(['kelas_id' => $kelasId, 'sks' => 3, 'status' => 'aktif']);

        // Pengajuan langsung ditolak kuota + disarankan waitlist.
        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 3.50)
            ->set('kelasDipilih', [$kelasId])
            ->call('ajukan')
            ->assertHasErrors(['kelasDipilih']);

        // Masuk waitlist berhasil dan idempoten (tidak ganda).
        $komponen = Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 3.50)
            ->call('masukWaitlist', $kelasId);
        $komponen->assertHasNoErrors();

        $this->assertSame(1, DB::table('kelas_waitlist')
            ->where('kelas_id', $kelasId)
            ->where('mahasiswa_id', $this->mahasiswaId)
            ->where('status', 'menunggu')
            ->count());

        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 3.50)
            ->call('masukWaitlist', $kelasId);

        $this->assertSame(1, DB::table('kelas_waitlist')
            ->where('kelas_id', $kelasId)
            ->where('mahasiswa_id', $this->mahasiswaId)
            ->count());
    }

    public function test_pa_tidak_bisa_menyetujui_krs_melanggar_batas_sks(): void
    {
        // KRS 21 SKS dengan snapshot IPK 1.00 (batas 18) — mis. diubah manual.
        $krs = Krs::factory()->create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'status' => Krs::STATUS_DIAJUKAN,
            'total_sks' => 21,
            'ipk_snapshot' => 1.00,
        ]);

        foreach ($this->kelasIds as $kelasId) {
            $krs->details()->create(['kelas_id' => $kelasId, 'sks' => 3, 'status' => 'aktif']);
        }

        Livewire::test(PersetujuanPa::class)->call('setujui', $krs->id);

        $this->assertSame(Krs::STATUS_DIAJUKAN, $krs->fresh()->status);
    }

    public function test_penolakan_pa_mempromosikan_waitlist(): void
    {
        $kelasId = $this->kelasIds[0];
        DB::table('kelas')->where('id', $kelasId)->update(['kuota' => 1]);

        $krs = Krs::factory()->create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'status' => Krs::STATUS_DIAJUKAN,
            'total_sks' => 3,
            'ipk_snapshot' => 3.50,
        ]);
        $krs->details()->create(['kelas_id' => $kelasId, 'sks' => 3, 'status' => 'aktif']);

        $penunggu = $this->buatMahasiswaLain();
        DB::table('kelas_waitlist')->insert([
            'kelas_id' => $kelasId,
            'mahasiswa_id' => $penunggu,
            'status' => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(PersetujuanPa::class)
            ->set('catatan', 'Jadwal tidak disarankan, silakan revisi.')
            ->call('tolak', $krs->id);

        $this->assertSame(Krs::STATUS_DITOLAK, $krs->fresh()->status);
        $this->assertSame('dipromosikan', DB::table('kelas_waitlist')
            ->where('kelas_id', $kelasId)
            ->where('mahasiswa_id', $penunggu)
            ->value('status'));
    }

    /**
     * Buat mahasiswa kedua (penghuni kuota / penunggu waitlist).
     */
    private function buatMahasiswaLain(): int
    {
        $user = User::factory()->create();

        return DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id,
            'nim' => '2026'.random_int(1002, 9999),
            'prodi_id' => DB::table('mahasiswa')->where('id', $this->mahasiswaId)->value('prodi_id'),
            'angkatan' => 2026,
            'status' => 'aktif',
        ]);
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

    public function test_krs_ditolak_dapat_direvisi_mahasiswa(): void
    {
        $krs = Krs::factory()->create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'status' => Krs::STATUS_DITOLAK,
            'total_sks' => 3,
            'ipk_snapshot' => 3.50,
        ]);
        $krs->details()->create(['kelas_id' => $this->kelasIds[0], 'sks' => 3, 'status' => 'aktif']);

        Livewire::test(Pengajuan::class, ['tahunId' => $this->tahunId])
            ->set('ipk', 3.50)
            ->call('toggleKelas', $this->kelasIds[1])
            ->call('ajukan')
            ->assertHasNoErrors();

        $this->assertSame(Krs::STATUS_DIAJUKAN, $krs->fresh()->status);
        $this->assertCount(2, $krs->fresh()->details);
    }
}
