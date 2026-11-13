<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_melaporkan_db_up_tanpa_migrasi_tertunda(): void
    {
        $this->get(route('health'))
            ->assertOk()
            ->assertJsonPath('db', 'up')
            ->assertJsonPath('pending_migrations', 0);
    }
}
