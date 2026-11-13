<?php

namespace Tests\Feature;

use App\Livewire\Nilai\InputNilai;
use App\Livewire\Nilai\KunciNilai;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Setting;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_va_ditolak_untuk_tagihan_lunas(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs = Mahasiswa::factory()->create(['user_id' => $user->id]);
        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs->id,
            'jenis' => 'UKT',
            'nominal' => 1000000,
            'terbayar' => 1000000,
            'status' => 'lunas',
        ]);

        $this->actingAs($user)->postJson(route('keuangan.bayar.va', $tagihan), ['method' => 'va'])
            ->assertStatus(422);
    }

    public function test_dosen_tidak_bisa_buka_kunci_nilai(): void
    {
        $dosenUser = User::factory()->create();
        $dosenUser->assignRole('dosen');
        $dosen = Dosen::factory()->create(['user_id' => $dosenUser->id]);
        $kelas = Kelas::factory()->create(['dosen_id' => $dosen->id]);

        Livewire::actingAs($dosenUser)
            ->test(KunciNilai::class, ['kelasId' => $kelas->id])
            ->call('buka')
            ->assertForbidden();
    }

    public function test_dosen_tidak_bisa_input_nilai_kelas_orang_lain(): void
    {
        $a = User::factory()->create();
        $a->assignRole('dosen');
        $dosenA = Dosen::factory()->create(['user_id' => $a->id]);
        $b = User::factory()->create();
        $b->assignRole('dosen');
        $dosenB = Dosen::factory()->create(['user_id' => $b->id]);
        $kelasB = Kelas::factory()->create(['dosen_id' => $dosenB->id]);

        Livewire::actingAs($a)
            ->test(InputNilai::class, ['kelasId' => $kelasB->id])
            ->assertForbidden();
    }

    public function test_tagihan_soft_delete_mengarsip_bukan_menghapus(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs->id,
            'jenis' => 'UKT',
            'nominal' => 1000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);

        $tagihan->delete();

        $this->assertSoftDeleted('tagihan', ['id' => $tagihan->id]);
        $this->assertNotNull(Tagihan::withTrashed()->find($tagihan->id));
    }

    public function test_gateway_retry_berhasil_setelah_gagal_transient(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs->id,
            'jenis' => 'UKT',
            'nominal' => 500000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);

        $flaky = new class implements PaymentGatewayInterface
        {
            public int $calls = 0;

            public function createTransaction(Tagihan $tagihan, array $customer): array
            {
                $this->calls++;
                if ($this->calls < 3) {
                    throw new \RuntimeException('timeout sementara');
                }

                return ['reference' => 'FLAKY-'.$tagihan->id, 'va_number' => '88880001', 'expires_at' => now()->addDay()];
            }

            public function verifyCallback(array $payload): ?string
            {
                return null;
            }

            public function checkStatus(string $reference): string
            {
                return 'pending';
            }

            public function requiredSettings(): array
            {
                return [];
            }
        };

        config(['payment.drivers.flaky' => ['class' => get_class($flaky), 'label' => 'Flaky']]);
        app()->instance(get_class($flaky), $flaky);
        Setting::set('pay.driver', 'flaky');

        $bayar = app(PaymentService::class)->createVa($tagihan, ['nama' => 'Tes']);

        $this->assertSame(3, $flaky->calls);
        $this->assertSame('FLAKY-'.$tagihan->id, $bayar->reference);
    }
}
