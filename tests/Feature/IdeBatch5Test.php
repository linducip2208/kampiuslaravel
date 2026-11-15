<?php

namespace Tests\Feature;

use App\Livewire\Krs\Pengajuan;
use App\Livewire\Lms\Forum;
use App\Models\Dosen;
use App\Models\ForumBalasan;
use App\Models\ForumThread;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 5b ide bank: kutipan forum, notifikasi PA, kunci brute-force.
 */
class IdeBatch5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** Kutipan balasan masuk ke draf dengan format kutip. */
    public function test_kutip_balasan_masuk_draf(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTQ']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'Q', 'jenjang' => 'S1', 'kode' => 'QQ', 'slug' => 'qq']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'QZ-1', 'nama' => 'QZ', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        $u = $this->user('mahasiswa');

        $thread = ForumThread::create(['kelas_id' => $kelas, 'dibuat_oleh' => $u->id, 'judul' => 'Tanya', 'isi' => 'Isi']);
        $balasan = ForumBalasan::create(['thread_id' => $thread->id, 'user_id' => $u->id, 'isi' => "Baris satu\nBaris dua"]);

        $draf = Livewire::actingAs($u)->test(Forum::class, ['kelasId' => $kelas])->call('kutip', $balasan->id)->get('balasan');

        $this->assertStringContainsString('> Baris satu', $draf);
        $this->assertStringContainsString('> Baris dua', $draf);
    }

    /** Pengajuan KRS sukses → 1 notifikasi untuk Dosen PA. */
    public function test_pengajuan_krs_memberi_notif_ke_pa(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T2', 'kode' => 'FT2']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'J', 'jenjang' => 'S1', 'kode' => 'JJ', 'slug' => 'jj']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => false]);
        $paUser = $this->user('dosen');
        $pa = Dosen::factory()->create(['user_id' => $paUser->id, 'prodi_id' => $prodi]);
        $mhsUser = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $mhsUser->id, 'nim' => '20209951', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif', 'dosen_pa_id' => $pa->id,
        ]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'KP-1', 'nama' => 'KP', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        Livewire::actingAs($mhsUser)->test(Pengajuan::class, ['tahunId' => $t])
            ->set('kelasDipilih', [$kelas])
            ->call('ajukan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $paUser->id,
            'judul' => 'KRS baru menunggu persetujuan',
        ]);
    }

    /** 5x gagal → kunci 15 menit; login benar ikut ditolak selama kunci. */
    public function test_kunci_akun_setelah_lima_kali_gagal(): void
    {
        $email = 'korban@kampus.ac.id';
        User::factory()->create(['email' => $email, 'password' => bcrypt('rahasia-benar-123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $email, 'password' => 'salah'])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 dengan password BENAR tetap ditolak (terkunci).
        $this->post('/login', ['email' => $email, 'password' => 'rahasia-benar-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('email') ?? ''
        );
    }
}
