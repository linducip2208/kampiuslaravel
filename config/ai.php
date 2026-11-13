<?php

use App\Services\Ai\OpenAiCompatibleDriver;
use App\Services\Ai\SimulatedAiDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider (TANPA hardcode)
    |--------------------------------------------------------------------------
    | Driver aktif dibaca dari tabel settings (kunci ai.*) via AiProvider,
    | nilai di bawah hanya default/fallback. Kredensial tidak di-hardcode.
    */
    'default_driver' => env('AI_DRIVER', 'simulated'),

    'drivers' => [
        'simulated' => ['class' => SimulatedAiDriver::class, 'label' => 'Simulasi (dev, tanpa API)'],
        'openai_compatible' => ['class' => OpenAiCompatibleDriver::class, 'label' => 'OpenAI-compatible (chat completions)'],
    ],

    'defaults' => [
        'model' => env('AI_MODEL', 'simulated-1'),
        'timeout' => (int) env('AI_TIMEOUT', 60),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 800),
    ],
];
