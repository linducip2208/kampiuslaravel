<?php

namespace Tests\Feature;

use App\Models\AuditBaca;
use App\Models\User;
use App\Services\AuditBacaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditBacaTest extends TestCase
{
    use RefreshDatabase;

    public function test_catat_menulis_baris_lengkap(): void
    {
        $user = User::factory()->create();

        AuditBacaService::catat($user->id, 'khs', 123, '127.0.0.1');

        $this->assertDatabaseHas('audit_baca', [
            'user_id' => $user->id,
            'model' => 'khs',
            'model_id' => 123,
            'ip' => '127.0.0.1',
        ]);

        $row = AuditBaca::first();
        $this->assertNotNull($row->dibaca_at);
    }

    public function test_catat_null_noop_tanpa_error(): void
    {
        AuditBacaService::catat(null, 'transkrip', 456);

        $this->assertDatabaseCount('audit_baca', 0);
    }

    public function test_retensi_menghapus_entri_lama_tapi_mempertahankan_yang_baru(): void
    {
        $user = User::factory()->create();

        $lama = AuditBaca::create([
            'user_id' => $user->id,
            'model' => 'khs',
            'model_id' => 1,
            'dibaca_at' => now()->subDays(400),
            'ip' => null,
        ]);
        AuditBaca::whereKey($lama->id)->update(['created_at' => now()->subDays(400)]);

        $baru = AuditBaca::create([
            'user_id' => $user->id,
            'model' => 'transkrip',
            'model_id' => 2,
            'dibaca_at' => now(),
            'ip' => null,
        ]);

        AuditBacaService::retensi();

        $this->assertDatabaseMissing('audit_baca', ['id' => $lama->id]);
        $this->assertDatabaseHas('audit_baca', ['id' => $baru->id]);
    }
}
