<?php

namespace Tests\Feature;

use App\Livewire\Analitik\AtRisk;
use App\Livewire\Krs\Pengajuan;
use App\Models\Dosen;
use App\Models\Notifikasi;
use App\Models\Tagihan;
use App\Models\Tugas;
use App\Models\TugasKumpul;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 7 ide bank: peringatan SKS, antrean di /health, notifikasi PA at-risk
 * (termasuk perbaikan bug penghalang: kolom `nama` tak ada di tabel
 * mahasiswa + kolom `is_late` tak ada di tugas_kumpul → daftar at-risk
 * selalu kosong karena exception ditelan try/catch).
 */
class Batch7Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function user(string $role, ?string $name = null): User
    {
        $u = User::factory()->create($name ? ['name' => $name] : []);
        $u->assignRole($role);

        return $u;
    }

    private function struktur(): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT7']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'K7', 'slug' => 'k7']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Peringatan dini: teks "Sisa SKS" tampil sebelum submit. */
    public function test_pengajuan_menampilkan_sisa_sks(): void
    {
        [$prodi, $t] = $this->struktur();
        $u = $this->user('mahasiswa');
        DB::table('mahasiswa')->insert(['user_id' => $u->id, 'nim' => '20209951', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);

        $html = Livewire::actingAs($u)->test(Pengajuan::class, ['tahunId' => $t])->html();
        $this->assertStringContainsString('Sisa SKS', $html);
    }

    /** /health melaporkan ukuran antrean + job gagal tanpa mengubah status ok. */
    public function test_health_melaporkan_antrean(): void
    {
        $this->get(route('health'))
            ->assertOk()
            ->assertJsonPath('db', 'up')
            ->assertJsonPath('queue_failed', 0)
            ->assertJsonStructure(['queue', 'queue_failed', 'checks' => ['queue']]);
    }

    /** Mahasiswa risiko tinggi → tombol kirim notifikasi ke Dosen PA. */
    public function test_at_risk_kirim_notifikasi_ke_pa(): void
    {
        [$prodi, $t] = $this->struktur();

        $paUser = $this->user('dosen', 'Dr. PA');
        $paDosen = Dosen::create(['user_id' => $paUser->id, 'nidn' => 'NIDN7', 'prodi_id' => $prodi]);

        $mhsUser = $this->user('mahasiswa', 'Mhs Berisiko');
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $mhsUser->id, 'nim' => '20209952', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif', 'dosen_pa_id' => $paDosen->id,
        ]);

        // Tanpa nilai IPK=0 (+40), 3 tunggakan (+15), 2 kumpul terlambat (+6) = 61 → tinggi.
        foreach (['UKT', 'SPP', 'lain'] as $jenis) {
            Tagihan::create([
                'mahasiswa_id' => $mhsId, 'tahun_akademik_id' => $t, 'jenis' => $jenis,
                'nominal' => 1000000, 'denda' => 0, 'terbayar' => 0, 'status' => 'belum_bayar',
            ]);
        }
        foreach (range(1, 2) as $i) {
            $tugas = Tugas::create(['judul' => "T{$i}"]);
            TugasKumpul::create(['tugas_id' => $tugas->id, 'mahasiswa_id' => $mhsId, 'is_late' => true]);
        }

        $adm = $this->user('superadmin');

        $ui = Livewire::actingAs($adm)->test(AtRisk::class);
        $ui->assertSee('20209952');
        $ui->assertSee('Kirim ke PA');

        $ui->call('notifikasiPa', $mhsId);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $paUser->id,
            'judul' => 'Mahasiswa bimbingan berisiko tinggi',
        ]);
        $this->assertStringContainsString('terkirim', (string) $ui->get('pesan'));
        $this->assertTrue(Notifikasi::where('user_id', $paUser->id)->exists());
    }
}
