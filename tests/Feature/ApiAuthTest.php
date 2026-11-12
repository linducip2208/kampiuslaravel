<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_gagal_dengan_kredensial_salah(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'nim_or_email' => 'tidakada@example.com',
            'password' => 'salah',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_berhasil_dengan_email(): void
    {
        $user = User::factory()->create([
            'email' => 'mhs@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'nim_or_email' => 'mhs@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame($user->id, $response->json('user.id'));
    }

    public function test_me_dan_logout_membutuhkan_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

        $this->getJson('/api/v1/me', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->postJson('/api/v1/logout', [], ['Authorization' => "Bearer {$token}"])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => 1]);
    }
}
