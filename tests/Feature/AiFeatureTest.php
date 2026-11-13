<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Services\Ai\AnomaliService;
use App\Services\Ai\AtRiskService;
use App\Services\Ai\EssayGradingService;
use App\Services\Ai\MateriAiService;
use App\Services\Ai\NotulenService;
use App\Services\Ai\RekomendasiMkService;
use App\Services\NotifikasiService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function token(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('mobile', ['mobile'])->plainTextToken];
    }

    public function test_chat_menjawab_ipk_dari_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        Mahasiswa::factory()->create(['user_id' => $user->id]);

        $this->postJson('/api/v1/ai/chat', ['pesan' => 'berapa IPK saya?'], $this->token($user))
            ->assertOk()
            ->assertJsonStructure(['data' => ['jawaban']]);

        $this->assertDatabaseHas('ai_jobs', ['jenis' => 'chat']);
    }

    public function test_at_risk_mahasiswa_melihat_skor_sendiri(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs = Mahasiswa::factory()->create(['user_id' => $user->id]);

        $res = $this->getJson('/api/v1/analitik/at-risk', $this->token($user))->assertOk();
        $res->assertJsonStructure(['data' => ['skor', 'level', 'faktor', 'rekomendasi']]);
        $this->assertNotNull($mhs->id);
    }

    public function test_staf_melihat_daftar_at_risk(): void
    {
        $staf = User::factory()->create();
        $staf->assignRole('superadmin');

        $this->getJson('/api/v1/analitik/at-risk', $this->token($staf))->assertOk();
    }

    public function test_rekomendasi_dan_grade_dan_ringkas_dan_notulen(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        Mahasiswa::factory()->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/ai/rekomendasi', $this->token($user))
            ->assertOk()->assertJsonStructure(['data' => ['batas_sks', 'ipk', 'ulangi', 'saran']]);

        $grade = app(EssayGradingService::class)->nilai(
            'Fotosintesis adalah proses tumbuhan hijau mengubah cahaya matahari menjadi energi kimia dengan klorofil dan air serta karbon dioksida menghasilkan glukosa dan oksigen.',
            'Fotosintesis mengubah cahaya matahari menjadi energi kimia memakai klorofil, air, dan karbon dioksida menghasilkan glukosa dan oksigen.'
        );
        $this->assertGreaterThanOrEqual(55, $grade['skor']);
        $this->assertContains($grade['huruf'], ['A', 'B', 'C']);

        $teks = 'Sistem informasi akademik mengelola data mahasiswa dan nilai. Kehadiran dicatat per pertemuan dengan kode QR. Tagihan UKT dibayar melalui virtual account bank. IPK dihitung dari bobot nilai dikali SKS.';
        $ringkas = app(MateriAiService::class)->ringkas($teks);
        $this->assertNotEmpty($ringkas['ringkasan']);
        $this->assertNotEmpty(app(MateriAiService::class)->buatQuiz($teks));

        $notulen = app(NotulenService::class)->notulen("Pembukaan rapat.\nDisepakati jadwal UAS minggu depan.\nTindak lanjut: diminta prodi menyiapkan ruangan sebelum deadline Jumat.");
        $this->assertNotEmpty($notulen['keputusan']);
        $this->assertNotEmpty($notulen['aksi']);

        $this->assertSame([], app(AnomaliService::class)->anomaliNilai(999999));
        $this->assertSame([], app(RekomendasiMkService::class)->rekomendasi(999999)['ulangi']);
        $this->assertSame('sedang', app(AtRiskService::class)->prediksi(999999)['level']);
    }

    public function test_halaman_chat_dan_at_risk(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        $staf = User::factory()->create();
        $staf->assignRole('superadmin');

        $this->actingAs($mhs)->get(route('ai.chat'))->assertOk();
        $this->actingAs($staf)->get(route('analitik.at-risk'))->assertOk();
    }

    public function test_notifikasi_menulis_notif_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');

        app(NotifikasiService::class)->send($user, 'Uji', 'Isi uji');

        $this->assertDatabaseHas('notif_log', ['user_id' => $user->id, 'channel' => 'db']);
    }
}
