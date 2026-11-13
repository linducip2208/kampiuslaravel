<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ComfyUIService
{
    public function __construct(
        private ?string $baseUrl = null,
        private ?string $clientId = null,
    ) {
        $this->baseUrl = $baseUrl ?: rtrim((string) (Setting::get('comfyui.url', '') ?: config('comfyui.url')), '/');
        $this->clientId = $clientId ?: (string) (config('comfyui.client_id') ?: Str::uuid()->toString());
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /** Antrekan workflow ke ComfyUI, return prompt_id. */
    public function queueWorkflow(array $workflow): string
    {
        $response = Http::timeout(30)->post($this->baseUrl.'/prompt', [
            'prompt' => $workflow,
            'client_id' => $this->clientId,
        ])->throw();

        $promptId = $response->json('prompt_id');

        if (! $promptId) {
            throw new RuntimeException('ComfyUI tidak mengembalikan prompt_id.');
        }

        return $promptId;
    }

    /** Tunggu hasil selesai, return daftar output [{filename, subfolder, type}]. */
    public function waitForResult(string $promptId, ?int $timeout = null): array
    {
        $timeout = $timeout ?? (int) config('comfyui.timeout', 600);
        $interval = max(1, (int) config('comfyui.poll_interval', 3));
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $history = Http::timeout(30)->get($this->baseUrl.'/history/'.$promptId)->throw()->json();

            if (isset($history[$promptId]['outputs']) && count($history[$promptId]['outputs']) > 0) {
                return $this->flattenOutputs($history[$promptId]['outputs']);
            }

            sleep($interval);
        }

        throw new RuntimeException('Timeout menunggu hasil ComfyUI (prompt_id: '.$promptId.').');
    }

    /** Download satu output ComfyUI, return binary. */
    public function fetchOutput(string $filename, string $subfolder = '', string $type = 'output'): string
    {
        return Http::timeout(120)->get($this->baseUrl.'/view', [
            'filename' => $filename,
            'subfolder' => $subfolder,
            'type' => $type,
        ])->throw()->body();
    }

    /**
     * Generate gambar dari prompt teks (default workflow WAN 2.1 T2I).
     * Return path relatif di disk config (mis. generated/uuid.png).
     */
    public function generateImage(string $prompt, array $options = []): string
    {
        $workflow = $this->buildWorkflow($prompt, $options);
        $promptId = $this->queueWorkflow($workflow);
        $outputs = $this->waitForResult($promptId, $options['timeout'] ?? null);

        $image = collect($outputs)->first(fn ($o) => $this->isImage($o['filename'] ?? ''));

        if (! $image) {
            throw new RuntimeException('ComfyUI tidak menghasilkan file gambar.');
        }

        $binary = $this->fetchOutput($image['filename'], $image['subfolder'] ?? '', $image['type'] ?? 'output');
        $ext = strtolower(pathinfo($image['filename'], PATHINFO_EXTENSION) ?: 'png');
        $path = trim((string) config('comfyui.prefix', 'generated'), '/').'/'.Str::uuid()->toString().'.'.$ext;

        Storage::disk((string) config('comfyui.disk', 'public'))->put($path, $binary);

        return $path;
    }

    /** Bangun workflow API-format dari template + injeksi prompt/seed/ukuran. */
    public function buildWorkflow(string $prompt, array $options = []): array
    {
        $template = $this->loadTemplate();
        $cfg = config('comfyui');

        $promptNode = (string) ($options['prompt_node'] ?? $cfg['prompt_node']);
        $negativeNode = (string) ($options['negative_node'] ?? $cfg['negative_node']);
        $seedNode = (string) ($options['seed_node'] ?? $cfg['seed_node']);
        $sizeNode = (string) ($options['size_node'] ?? $cfg['size_node']);

        if (isset($template[$promptNode]['inputs']['text'])) {
            $template[$promptNode]['inputs']['text'] = $prompt;
        }

        if (isset($template[$negativeNode]['inputs']['text'])) {
            $template[$negativeNode]['inputs']['text'] = $options['negative'] ?? $cfg['defaults']['negative'];
        }

        if (isset($template[$seedNode]['inputs']['seed'])) {
            $template[$seedNode]['inputs']['seed'] = (int) ($options['seed'] ?? $cfg['defaults']['seed'] ?: random_int(1, 999999999));
        }

        if (isset($template[$seedNode]['inputs']['steps'])) {
            $template[$seedNode]['inputs']['steps'] = (int) ($options['steps'] ?? $cfg['defaults']['steps']);
        }

        if (isset($template[$sizeNode]['inputs']['width'])) {
            $template[$sizeNode]['inputs']['width'] = (int) ($options['width'] ?? $cfg['defaults']['width']);
        }

        if (isset($template[$sizeNode]['inputs']['height'])) {
            $template[$sizeNode]['inputs']['height'] = (int) ($options['height'] ?? $cfg['defaults']['height']);
        }

        return $template;
    }

    private function loadTemplate(): array
    {
        $file = (string) (Setting::get('comfyui.workflow_file', '') ?: config('comfyui.workflow_file'));

        if ($file !== '' && is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Template bawaan: text-to-image generik (kompatibel workflow SD1.5/SDXL API-format).
        // Untuk WAN 2.1, export workflow API-format dari ComfyUI lalu isi COMFYUI_WORKFLOW_FILE.
        return [
            '3' => ['class_type' => 'KSampler', 'inputs' => ['seed' => 0, 'steps' => 20, 'cfg' => 7.0, 'sampler_name' => 'euler', 'scheduler' => 'normal', 'denoise' => 1.0, 'model' => ['4', 0], 'positive' => ['6', 0], 'negative' => ['7', 0], 'latent_image' => ['5', 0]]],
            '4' => ['class_type' => 'CheckpointLoaderSimple', 'inputs' => ['ckpt_name' => (string) (Setting::get('comfyui.model', '') ?: config('comfyui.model'))]],
            '5' => ['class_type' => 'EmptyLatentImage', 'inputs' => ['width' => 832, 'height' => 1216, 'batch_size' => 1]],
            '6' => ['class_type' => 'CLIPTextEncode', 'inputs' => ['text' => '', 'clip' => ['4', 1]]],
            '7' => ['class_type' => 'CLIPTextEncode', 'inputs' => ['text' => '', 'clip' => ['4', 1]]],
            '8' => ['class_type' => 'VAEDecode', 'inputs' => ['samples' => ['3', 0], 'vae' => ['4', 2]]],
            '9' => ['class_type' => 'SaveImage', 'inputs' => ['images' => ['8', 0], 'filename_prefix' => 'simak']],
        ];
    }

    /** @param array<string, mixed> $outputs */
    private function flattenOutputs(array $outputs): array
    {
        $files = [];

        foreach ($outputs as $node) {
            foreach (['images', 'gifs'] as $key) {
                foreach ($node[$key] ?? [] as $file) {
                    $files[] = [
                        'filename' => $file['filename'],
                        'subfolder' => $file['subfolder'] ?? '',
                        'type' => $file['type'] ?? 'output',
                    ];
                }
            }
        }

        return $files;
    }

    private function isImage(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp'], true);
    }
}
