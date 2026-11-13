<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AkademikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrasyaratTest extends TestCase
{
    use RefreshDatabase;

    private int $mahasiswaId;

    private int $kelasA;

    private int $kelasB;

    private int $kelasC;

    protected function setUp(): void
    {
        parent::setUp();

        $fak = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fak, 'nama' => 'Informatika', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'informatika',
        ]);
        $tahun = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);
        $user = User::factory()->create();
        $this->mahasiswaId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => '20260999', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif',
        ]);

        $mkA = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'PRA101', 'nama' => 'Dasar', 'sks' => 3, 'semester' => 1]);
        $mkB = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'PRA102', 'nama' => 'Dasar 2', 'sks' => 3, 'semester' => 1]);
        $mkC = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'LAN201', 'nama' => 'Lanjutan', 'sks' => 3, 'semester' => 2]);

        // Dua prasyarat via relasi ganda (bukan kolom legacy).
        DB::table('mk_prasyarat')->insert([
            ['matakuliah_id' => $mkC, 'prasyarat_id' => $mkA, 'jenis' => 'prasyarat'],
            ['matakuliah_id' => $mkC, 'prasyarat_id' => $mkB, 'jenis' => 'prasyarat'],
        ]);

        $this->kelasA = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkA, 'tahun_akademik_id' => $tahun, 'nama' => 'A', 'kuota' => 40]);
        $this->kelasB = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkB, 'tahun_akademik_id' => $tahun, 'nama' => 'A', 'kuota' => 40]);
        $this->kelasC = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkC, 'tahun_akademik_id' => $tahun, 'nama' => 'A', 'kuota' => 40]);

        // Lulus A saja.
        DB::table('nilai')->insert([
            'mahasiswa_id' => $this->mahasiswaId, 'kelas_id' => $this->kelasA,
            'tugas' => 80, 'uts' => 80, 'uas' => 80, 'kehadiran' => 100,
            'na' => 82, 'huruf' => 'B', 'bobot' => 3.0,
        ]);
    }

    public function test_prasyarat_ganda_dicek_semua(): void
    {
        $pesan = AkademikService::validasiKrs($this->mahasiswaId, [$this->kelasC], 3.50);

        $this->assertNotEmpty(array_filter($pesan, fn ($m) => str_contains($m, 'Prasyarat')));
    }

    public function test_lolos_setelah_semua_prasyarat_lulus(): void
    {
        DB::table('nilai')->insert([
            'mahasiswa_id' => $this->mahasiswaId, 'kelas_id' => $this->kelasB,
            'tugas' => 80, 'uts' => 80, 'uas' => 80, 'kehadiran' => 100,
            'na' => 82, 'huruf' => 'B', 'bobot' => 3.0,
        ]);

        $pesan = AkademikService::validasiKrs($this->mahasiswaId, [$this->kelasC], 3.50);

        $this->assertEmpty(array_filter($pesan, fn ($m) => str_contains($m, 'Prasyarat')));
    }
}
