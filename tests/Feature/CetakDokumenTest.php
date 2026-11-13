<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\Surat;
use App\Models\User;
use App\Support\DokumenTte;
use App\Support\QrSvg;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regresi dokumen cetak: KTM digital + QR verifikasi, KHS/transkrip + TTE,
 * dan template 4 jenis surat.
 */
class CetakDokumenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function mahasiswaUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        Mahasiswa::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function ownMahasiswa(User $user): Mahasiswa
    {
        return Mahasiswa::where('user_id', $user->id)->firstOrFail();
    }

    public function test_qr_svg_struktur_valid(): void
    {
        $m = (new QrSvg)->matrix('A');

        $this->assertCount(21, $m);
        $this->assertCount(21, $m[0]);
        // Pola finder kiri-atas: sudut gelap, tengah gelap, cincin dalam terang.
        $this->assertSame(1, $m[0][0]);
        $this->assertSame(1, $m[0][6]);
        $this->assertSame(1, $m[6][0]);
        $this->assertSame(1, $m[3][3]);
        $this->assertSame(0, $m[1][1]);
        // Timing & modul gelap.
        $this->assertSame(1, $m[6][8]);
        $this->assertSame(0, $m[6][9]);
        $this->assertSame(1, $m[13][8]);

        foreach ($m as $row) {
            foreach ($row as $v) {
                $this->assertContains($v, [0, 1]);
            }
        }

        $this->assertStringContainsString('<svg', QrSvg::svg('A'));
    }

    public function test_qr_svg_versi_mengikuti_panjang(): void
    {
        $this->assertSame(1, (new QrSvg)->versionFor('A'));
        $this->assertCount(29, (new QrSvg)->matrix(str_repeat('A', 40)));
        $this->assertNull(QrSvg::trySvg(str_repeat('A', 200)));

        $this->expectException(\RuntimeException::class);
        (new QrSvg)->matrix(str_repeat('A', 200));
    }

    public function test_qr_svg_format_kedua_copy_valid_bch(): void
    {
        foreach (['A', 'https://kampus.example/ktm/verifikasi/2026001234', str_repeat('X', 100)] as $text) {
            $m = (new QrSvg)->matrix($text);
            $n = count($m);

            $first = [];
            for ($i = 0; $i <= 5; $i++) {
                $first[] = $m[8][$i];
            }
            $first[] = $m[8][7];
            $first[] = $m[8][8];
            $first[] = $m[7][8];
            for ($i = 9; $i < 15; $i++) {
                $first[] = $m[14 - $i][8];
            }

            $secondVal = 0;
            for ($i = 8; $i < 15; $i++) {
                $secondVal |= ($m[$n - 7 + ($i - 8)][8] << $i);
            }
            for ($i = 0; $i < 8; $i++) {
                $secondVal |= ($m[8][$n - 1 - $i] << $i);
            }

            $val = 0;
            foreach ($first as $i => $b) {
                $val |= ($b << $i);
            }

            $this->assertSame($val, $secondVal, 'Salinan format tidak konsisten untuk: '.$text);
            $unmasked = $val ^ 0x5412;
            $rem = $unmasked >> 10;
            for ($i = 0; $i < 10; $i++) {
                $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
            }
            $this->assertSame($unmasked & 0x3FF, $rem & 0x3FF, 'BCH format tidak valid untuk: '.$text);
            $this->assertSame(0b01, ($unmasked >> 13) & 0b11, 'Level EC harus L.');
        }
    }

    public function test_khs_cetak_memuat_qr_nim_dan_tte(): void
    {
        $user = $this->mahasiswaUser();
        $mhs = $this->ownMahasiswa($user);

        $res = $this->actingAs($user)->get(route('khs.show', $mhs->id));

        $res->assertOk();
        $res->assertSee($mhs->nim);
        $res->assertSee('Ditandatangani secara elektronik');
        $res->assertSee('<svg', false);
        $res->assertSee('verifikasi', false);
    }

    public function test_khs_milik_orang_lain_ditolak(): void
    {
        $a = $this->mahasiswaUser();
        $b = $this->mahasiswaUser();
        $mhsB = $this->ownMahasiswa($b);

        $this->actingAs($a)->get(route('khs.show', $mhsB->id))->assertForbidden();
        $this->actingAs($a)->get(route('transkrip.cetak', $mhsB->id))->assertForbidden();
        $this->actingAs($a)->get(route('ktm.cetak', $mhsB->id))->assertForbidden();
    }

    public function test_khs_verify_butuh_tanda_tangan(): void
    {
        $user = $this->mahasiswaUser();
        $mhs = $this->ownMahasiswa($user);

        $this->get(route('khs.verify', ['mahasiswa' => $mhs->id]))->assertForbidden();
        $this->get(route('khs.verify', ['mahasiswa' => $mhs->id, 's' => 'salah']))
            ->assertForbidden();

        $url = route('khs.verify', ['mahasiswa' => $mhs->id, 's' => DokumenTte::tanda('khs:'.$mhs->id)]);
        $this->get($url)->assertOk()->assertSee('VALID');
        $this->getJson($url)->assertOk()->assertJson(['dokumen' => 'khs', 'valid' => true]);
    }

    public function test_transkrip_cetak_dan_verify(): void
    {
        $user = $this->mahasiswaUser();
        $mhs = $this->ownMahasiswa($user);

        $this->actingAs($user)->get(route('transkrip.cetak', $mhs->id))
            ->assertOk()
            ->assertSee('Predikat')
            ->assertSee('<svg', false);

        $url = route('transkrip.verify', ['mahasiswa' => $mhs->id, 's' => DokumenTte::tanda('transkrip:'.$mhs->id)]);
        $this->get($url)->assertOk()->assertSee('VALID');
        $this->getJson($url)->assertOk()->assertJson(['dokumen' => 'transkrip', 'valid' => true]);
        $this->get(route('transkrip.verify', ['mahasiswa' => $mhs->id, 's' => 'salah']))->assertForbidden();
    }

    public function test_ktm_cetak_dan_verify_nim(): void
    {
        $user = $this->mahasiswaUser();
        $mhs = $this->ownMahasiswa($user);

        $this->actingAs($user)->get(route('ktm.cetak', $mhs->id))
            ->assertOk()
            ->assertSee($mhs->nim)
            ->assertSee('KARTU TANDA MAHASISWA')
            ->assertSee('<svg', false);

        $this->get(route('ktm.verify', ['nim' => $mhs->nim]))->assertOk()->assertSee('VALID');
        $this->getJson(route('ktm.verify', ['nim' => $mhs->nim]))
            ->assertOk()
            ->assertJson(['dokumen' => 'ktm', 'nim' => $mhs->nim, 'valid' => true]);
        $this->get(route('ktm.verify', ['nim' => 'TIDAKADA']))->assertNotFound();
    }

    #[DataProvider('jenisSurat')]
    public function test_surat_empat_jenis_memiliki_template(string $jenis, string $judul): void
    {
        $user = $this->mahasiswaUser();
        $mhs = $this->ownMahasiswa($user);

        $surat = Surat::create([
            'mahasiswa_id' => $mhs->id,
            'jenis' => $jenis,
            'keperluan' => 'Keperluan uji.',
            'nomor_surat' => strtoupper($jenis).'/UNIV/2026/0001',
            'status' => 'selesai',
        ]);

        $this->actingAs($user)->get(route('surat.cetak', $surat->id))
            ->assertOk()
            ->assertSee($judul)
            ->assertSee('Ditandatangani secara elektronik')
            ->assertSee('<svg', false);

        $this->getJson(route('surat.verify', ['nomor' => $surat->nomor_surat]))
            ->assertOk()
            ->assertJson(['valid' => true, 'jenis' => $jenis]);
    }

    public static function jenisSurat(): array
    {
        return [
            'aktif kuliah' => ['aktif_kuliah', 'Surat Keterangan Aktif Kuliah'],
            'pengantar KP' => ['pengantar_kp', 'Surat Pengantar Kerja Praktik'],
            'keterangan lulus' => ['keterangan_lulus', 'Surat Keterangan Lulus'],
            'legalisir' => ['legalisir', 'Surat Permohonan Legalisir'],
        ];
    }

    public function test_surat_tanpa_nomor_ditolak(): void
    {
        $user = $this->mahasiswaUser();
        $mhs = $this->ownMahasiswa($user);

        $surat = Surat::create([
            'mahasiswa_id' => $mhs->id,
            'jenis' => 'aktif_kuliah',
            'keperluan' => 'Uji.',
            'status' => 'diajukan',
        ]);

        $this->actingAs($user)->get(route('surat.cetak', $surat->id))->assertForbidden();
    }
}
