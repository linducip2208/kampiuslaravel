<?php

namespace App\Jobs;

use App\Models\Pengumuman;
use App\Services\ComfyUIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMediaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $prompt,
        public array $options = [],
        public ?int $pengumumanId = null,
    ) {}

    public function handle(ComfyUIService $comfy): void
    {
        $path = $comfy->generateImage($this->prompt, $this->options);

        if ($this->pengumumanId) {
            Pengumuman::whereKey($this->pengumumanId)->update(['cover_path' => $path]);
        }
    }
}
