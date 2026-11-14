<?php

use App\Services\ComfyUIService;
use Illuminate\Contracts\Console\Kernel;

// Generate aset KAMPIUS via ComfyUI WAN 2.1 lokal (tanpa API eksternal).
// Jalankan SETELAH server ComfyUI up: php gen-assets.php
// Hasil: storage/app/public/generated/*.png + daftar path JSON.
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$assets = [
    // [key, prompt, width, height, steps, seed]
    ['logo-emblem', 'premium university logo emblem, stylized letter K combined with graduation cap, flat vector style, navy blue and bright blue gradient on pure white background, minimal, symmetrical, no text, no letters besides K shape, clean corporate identity', 1024, 1024, 24, 7101],
    ['hero-kampus', 'modern Indonesian university campus building with glass facade at bright daylight, blue sky, green lawn in front, students walking with backpacks, photorealistic wide angle, clean professional education photography, vibrant', 1280, 720, 20, 7102],
    ['keuangan', 'abstract financial data visualization, rising bar charts and glowing blue line graphs, navy dark background with cyan highlights, premium fintech illustration, clean, no text, no numbers', 1024, 768, 20, 7103],
    ['akademik', 'university students studying with laptops in bright modern classroom, glass windows, daylight, photorealistic, professional education photography, diverse Indonesian students', 1024, 768, 20, 7104],
    ['wisuda', 'university graduation ceremony, graduates in black gowns and caps throwing caps in air, campus building behind, daylight, photorealistic, joyful, professional photography', 1024, 768, 20, 7105],
    ['keamanan', 'cybersecurity shield emblem with circuit pattern, navy blue and cyan glow on dark background, premium tech illustration, clean, no text', 1024, 1024, 20, 7106],
];

$neg = 'blurry, low quality, watermark, text, distorted, ugly, deformed';
$svc = new ComfyUIService;
$out = [];
foreach ($assets as [$key, $prompt, $w, $h, $steps, $seed]) {
    echo ">> $key ...".PHP_EOL;
    try {
        $t = microtime(true);
        $path = $svc->generateImage($prompt, [
            'width' => $w, 'height' => $h, 'steps' => $steps, 'seed' => $seed,
            'negative' => $neg, 'timeout' => 1200,
        ]);
        $out[$key] = $path;
        echo '   OK '.round(microtime(true) - $t).'s => '.$path.PHP_EOL;
    } catch (Throwable $e) {
        echo '   GAGAL: '.get_class($e).': '.$e->getMessage().PHP_EOL;
        $out[$key] = null;
    }
}
file_put_contents(__DIR__.'/storage/app/public/generated/manifest-kampus.json', json_encode($out, JSON_PRETTY_PRINT));
echo "MANIFEST: storage/app/public/generated/manifest-kampus.json\n";
