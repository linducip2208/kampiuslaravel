<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ComfyUI Server (generate gambar, default model WAN 2.1)
    |--------------------------------------------------------------------------
    | Semua nilai bisa diubah via .env / halaman admin tanpa hardcode.
    | Lihat docs/COMFYUI.md untuk cara export workflow API-format + model WAN 2.1.
    */
    'url' => env('COMFYUI_URL', 'http://127.0.0.1:8188'),
    'client_id' => env('COMFYUI_CLIENT_ID'),
    'timeout' => (int) env('COMFYUI_TIMEOUT', 600),
    'poll_interval' => (int) env('COMFYUI_POLL_INTERVAL', 3),

    // Model default. Ganti via .env COMFYUI_MODEL (mis. checkpoint SDXL dsb).
    'model' => env('COMFYUI_MODEL', 'wan2.1'),

    // Node ID di workflow template untuk injeksi prompt (format API ComfyUI).
    'prompt_node' => env('COMFYUI_PROMPT_NODE', '6'),
    'negative_node' => env('COMFYUI_NEGATIVE_NODE', '7'),
    'seed_node' => env('COMFYUI_SEED_NODE', '3'),
    'size_node' => env('COMFYUI_SIZE_NODE', '5'),

    // Path file JSON workflow API-format. Kosong = pakai template bawaan (WAN 2.1 T2I).
    'workflow_file' => env('COMFYUI_WORKFLOW_FILE', ''),

    'defaults' => [
        'negative' => env('COMFYUI_DEFAULT_NEGATIVE', 'blurry, low quality, watermark, text'),
        'steps' => (int) env('COMFYUI_DEFAULT_STEPS', 20),
        'width' => (int) env('COMFYUI_DEFAULT_WIDTH', 832),
        'height' => (int) env('COMFYUI_DEFAULT_HEIGHT', 1216),
        'seed' => (int) env('COMFYUI_DEFAULT_SEED', 0),
    ],

    'disk' => env('COMFYUI_DISK', 'public'),
    'prefix' => env('COMFYUI_PREFIX', 'generated'),
];
