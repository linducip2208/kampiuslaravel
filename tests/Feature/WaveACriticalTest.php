<?php

namespace Tests\Feature;

use App\Livewire\Dosen\WaliList;
use App\Livewire\Keuangan\BayarManual;
use App\Livewire\Krs\Pengajuan;
use App\Models\Krs;
use App\Models\Pembayaran;
use App\Models\PmbPendaftar;
use App\Models\Setting;
use App\Models\Tagihan;
use App\Models\TagihanCicilan;
use App\Models\User;
use App\Services\KeuanganService;
use App\Services\PmbVaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tes penerimaan Wave A (MEGA): kebenaran akademik + anti-race keuangan.
 * Setiap tes menunjuk ke ID temuan MEGA_AUDIT_2026.
 */
class WaveACriticalTest extends TestCase
{
    use RefreshDatabase;

    private function strukturAkademik(): array
    {
        $this->seed(RolesPermissionsSeeder::class);

        $tahun1 = DB::table('tahun_akademik')->insertGetId(['tahun' => '2025', 'semester' => 'ganjil', 'aktif' => false]);
        $tahun2 = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'genap', 'aktif' => true]);
        $fakultas = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fakultas, 'nama' => 'Informatika', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'informatika',
        ]);

        return [$tahun1, $tahun2, $prodi];
    }

    private function mahasiswaDenganUser(string $nim, int $prodi, string $role = 'mahasiswa'): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $id = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        return [$user, $id];
    }

    private function kelas(int $prodi, int $tahunId, string $kode, int $sks): int
    {
        $mk = DB::table('matakuliah')->insertGetId([
            'prodi_id' => $prodi, 'kode' => $kode, 'nama' => 'MK '.$kode, 'sks' => $sks, 'semester' => 1,
        ]);

        return DB::table('kelas')->insertGetId([
            'matakuliah_id' => $mk, 'tahun_akademik_id' => $tahunId, 'nama' => 'A', 'kuota' => 40,
        ]);
    }

    /** M-007: IP semester = IP tahun terbaru, bukan IPK global. */
    public function test_khs_ip_semester_bukan_ipk_global(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$user, $mhs] = $this->mahasiswaDenganUser('20260001', $prodi);

        DB::table('nilai')->insert([
            'mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $tahun1, 'A1', 3), 'bobot' => 4.0, 'is_locked' => true,
        ]);
        DB::table('nilai')->insert([
            'mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $tahun2, 'A2', 3), 'bobot' => 1.0, 'is_locked' => true,
        ]);

        $data = $this->actingAs($user)->get(route('khs.show', $mhs))->assertOk()->viewData();

        $this->assertEquals(2.5, $data['ipk']); // (12+3)/6 global
        $this->assertEquals(1.0, $data['ip']); // IP tahun terbaru saja
    }

    /** M-008: input IPK dari browser diabaikan — batas SKS & snapshot dari server. */
    public function test_krs_abaikan_ipk_input_manual(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$user, $mhs] = $this->mahasiswaDenganUser('20260002', $prodi);

        $kelas = [];
        foreach ([['B1', 3], ['B2', 3], ['B3', 2], ['B4', 3], ['B5', 3], ['B6', 4], ['B7', 3]] as [$kode, $sks]) {
            $kelas[] = $this->kelas($prodi, $tahun2, $kode, $sks);
        }

        // Klaim IPK 4.0 (batas 24) padahal server tahu IPK 0.0 (batas 18): 21 SKS tetap ditolak.
        Livewire::actingAs($user)->test(Pengajuan::class, ['tahunId' => $tahun2])
            ->set('ipk', 4.0)
            ->set('kelasDipilih', $kelas)
            ->call('ajukan')
            ->assertHasErrors(['kelasDipilih']);

        $this->assertNull(Krs::query()->where('mahasiswa_id', $mhs)->first());

        // Lolos dalam batas server: snapshot tercatat 0.0 (bukan 4.0 dari input).
        Livewire::actingAs($user)->test(Pengajuan::class, ['tahunId' => $tahun2])
            ->set('ipk', 4.0)
            ->set('kelasDipilih', [$kelas[0], $kelas[1]])
            ->call('ajukan')
            ->assertHasNoErrors();

        $this->assertEquals(0.0, (float) Krs::query()->where('mahasiswa_id', $mhs)->value('ipk_snapshot'));
    }

    /** M-009: IPK wali berbobot SKS, sama dengan AkademikService::ringkasan. */
    public function test_wali_ipk_berbobot_sks(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$admin] = $this->mahasiswaDenganUser('ADM001', $prodi, 'superadmin');
        [, $mhs] = $this->mahasiswaDenganUser('20260003', $prodi);

        DB::table('nilai')->insert([
            'mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $tahun2, 'C1', 4), 'bobot' => 4.0, 'is_locked' => true,
        ]);
        DB::table('nilai')->insert([
            'mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $tahun2, 'C2', 1), 'bobot' => 1.0, 'is_locked' => true,
        ]);

        $ipk = Livewire::actingAs($admin)->test(WaliList::class)->viewData('ipk');

        // (16+1)/5 = 3.4 — AVG(bobot) lama akan menjawab 2.5.
        $this->assertEqualsWithDelta(3.4, (float) $ipk[$mhs], 0.01);
    }

    /** M-001: callback PMBVA tanpa signature valid ditolak di driver nyata. */
    public function test_callback_pmbva_tanpa_signature_ditolak(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        Setting::set('pay.driver', 'xendit');

        $pendaftar = PmbPendaftar::create([
            'no_daftar' => 'PMB-WAVE-A-1', 'nama' => 'Tes', 'email' => 't@t.id', 'hp' => '081',
            'va_reference' => 'PMBVA-9-ABCDEF', 'bayar_status' => 'belum_bayar',
        ]);

        $this->postJson(route('payment.callback'), ['reference' => 'PMBVA-9-ABCDEF'])
            ->assertStatus(400);

        $this->assertSame('belum_bayar', $pendaftar->fresh()->bayar_status);
    }

    /** M-002: settle PMBVA ganda idempoten. */
    public function test_pmbva_settle_ganda_idempoten(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $svc = app(PmbVaService::class);

        PmbPendaftar::create([
            'no_daftar' => 'PMB-WAVE-A-2', 'nama' => 'Tes', 'email' => 't2@t.id', 'hp' => '082',
            'va_reference' => 'PMBVA-9-GANDA', 'bayar_status' => 'belum_bayar',
        ]);

        $this->assertTrue($svc->settleByReference('PMBVA-9-GANDA'));
        $this->assertFalse($svc->settleByReference('PMBVA-9-GANDA'));
        $this->assertSame('lunas', PmbPendaftar::where('va_reference', 'PMBVA-9-GANDA')->value('bayar_status'));
    }

    /** M-003: dua submit bukti manual bersamaan hanya menghasilkan satu pending. */
    public function test_bayar_manual_ganda_hanya_satu_pending(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$user, $mhs] = $this->mahasiswaDenganUser('20260004', $prodi);
        Storage::fake('public');

        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs, 'tahun_akademik_id' => $tahun2,
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        Livewire::actingAs($user)->test(BayarManual::class)
            ->set('tagihan_id', $tagihan->id)
            ->set('bukti', UploadedFile::fake()->image('bukti.jpg'))
            ->call('simpan')
            ->assertHasNoErrors();

        Livewire::actingAs($user)->test(BayarManual::class)
            ->set('tagihan_id', $tagihan->id)
            ->set('bukti', UploadedFile::fake()->image('bukti2.jpg'))
            ->call('simpan')
            ->assertHasErrors(['tagihan_id']);

        $this->assertSame(1, DB::table('pembayaran')
            ->where('tagihan_id', $tagihan->id)->where('status', 'pending')->count());
    }

    /** M-011: piutang pimpinan memakai definisi yang sama (nominal+denda-terbayar). */
    public function test_piutang_pimpinan_termasuk_denda(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$user] = $this->mahasiswaDenganUser('20260005', $prodi, 'pimpinan');

        Tagihan::create([
            'mahasiswa_id' => DB::table('mahasiswa')->where('nim', '20260005')->value('id'),
            'tahun_akademik_id' => $tahun2, 'jenis' => 'UKT',
            'nominal' => 1000000, 'denda' => 50000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        $stats = $this->actingAs($user)->get(route('dashboard.pimpinan'))->assertOk()->viewData('stats');

        $this->assertSame(1050000, (int) $stats['piutang']);
    }

    /** M-012: jurnal penerimaan menolak nominal non-positif. */
    public function test_jurnal_menolak_nominal_nol(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$user, $mhs] = $this->mahasiswaDenganUser('20260006', $prodi);

        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs, 'tahun_akademik_id' => $tahun2,
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);
        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'manual', 'nominal' => 0, 'status' => 'pending',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        KeuanganService::postPenerimaan($bayar);
    }

    /** M-014: cicilan soft-delete + restore. */
    public function test_cicilan_soft_delete_dan_restore(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$user, $mhs] = $this->mahasiswaDenganUser('20260007', $prodi);

        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs, 'tahun_akademik_id' => $tahun2,
            'jenis' => 'UKT', 'nominal' => 1200000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);
        $cicilan = TagihanCicilan::create([
            'tagihan_id' => $tagihan->id, 'urutan' => 1, 'nominal' => 600000,
            'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        $cicilan->delete();
        $this->assertSoftDeleted('tagihan_cicilan', ['id' => $cicilan->id]);
        $this->assertSame(0, TagihanCicilan::where('tagihan_id', $tagihan->id)->count());

        $cicilan->restore();
        $this->assertSame(1, TagihanCicilan::where('tagihan_id', $tagihan->id)->count());
    }

    /** M-015: Policy — pemilik boleh, mahasiswa lain ditolak (HTTP). */
    public function test_policy_pemilik_vs_mahasiswa_lain(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$userA, $mhsA] = $this->mahasiswaDenganUser('20260008', $prodi);
        [$userB] = $this->mahasiswaDenganUser('20260009', $prodi);

        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhsA, 'tahun_akademik_id' => $tahun2,
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);
        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'manual', 'nominal' => 1000000, 'status' => 'pending',
        ]);

        $this->actingAs($userA)->getJson(route('keuangan.bayar.status', $bayar))->assertOk();
        $this->actingAs($userB)->getJson(route('keuangan.bayar.status', $bayar))->assertForbidden();
        $this->actingAs($userB)->postJson(route('keuangan.bayar.va', $tagihan))->assertForbidden();
    }

    /** M-015: matriks Policy — dosen boleh lihat, tidak boleh bayar; refund khusus keuangan. */
    public function test_policy_matriks_dosen_dan_refund(): void
    {
        [$tahun1, $tahun2, $prodi] = $this->strukturAkademik();
        [$userA, $mhsA] = $this->mahasiswaDenganUser('20260010', $prodi);
        [$dosen] = $this->mahasiswaDenganUser('DSN001', $prodi, 'dosen');
        [$keu] = $this->mahasiswaDenganUser('KEU001', $prodi, 'keuangan');

        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhsA, 'tahun_akademik_id' => $tahun2,
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);
        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'manual', 'nominal' => 1000000, 'status' => 'pending',
        ]);

        $this->assertTrue(Gate::forUser($userA)->allows('view', $bayar));
        $this->assertTrue(Gate::forUser($dosen)->allows('view', $bayar));
        $this->assertFalse(Gate::forUser($dosen)->allows('bayar', $tagihan));
        $this->assertFalse(Gate::forUser($userA)->allows('refund', $bayar));
        $this->assertTrue(Gate::forUser($keu)->allows('refund', $bayar));
    }
}
