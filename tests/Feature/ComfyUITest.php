<?php

namespace Tests\Feature;

use App\Services\ComfyUIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComfyUITest extends TestCase
{
    use RefreshDatabase;

    public function test_build_workflow_injects_prompt(): void
    {
        $service = new ComfyUIService('http://127.0.0.1:8188', 'test-client');
        $workflow = $service->buildWorkflow('kampus di pagi hari', ['seed' => 42, 'steps' => 10]);

        $this->assertSame('kampus di pagi hari', $workflow['6']['inputs']['text']);
        $this->assertSame(42, $workflow['3']['inputs']['seed']);
        $this->assertSame(10, $workflow['3']['inputs']['steps']);
    }

    public function test_generate_image_stores_file(): void
    {
        Storage::fake('public');

        Http::fake([
            '127.0.0.1:8188/prompt' => Http::response(['prompt_id' => 'abc123'], 200),
            '127.0.0.1:8188/history/*' => Http::response([
                'abc123' => ['outputs' => ['9' => ['images' => [
                    ['filename' => 'simak_001.png', 'subfolder' => '', 'type' => 'output'],
                ]]]],
            ], 200),
            '127.0.0.1:8188/view*' => Http::response('fake-png-binary', 200),
        ]);

        $service = new ComfyUIService('http://127.0.0.1:8188', 'test-client');
        $path = $service->generateImage('poster PMB');

        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('generated/', $path);
    }

    public function test_publik_prodi_pages_render(): void
    {
        $this->get(route('publik.prodi'))->assertOk();
        $this->get(route('publik.pengumuman'))->assertOk();
    }
}
