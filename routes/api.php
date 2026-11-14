<?php

use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\V1\AbsensiController;
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BimbinganController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\JadwalController;
use App\Http\Controllers\Api\V1\KrsController;
use App\Http\Controllers\Api\V1\NilaiController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\PengumumanController;
use App\Http\Controllers\Api\V1\TagihanController;
use App\Http\Controllers\Api\V1\TugasController;
use App\Http\Controllers\Api\V2\PublikApiController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [OpenApiController::class, 'show'])->middleware('throttle:60,1');

Route::prefix('v1')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'abilities:mobile'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/jadwal-hari-ini', [JadwalController::class, 'hariIni']);
        Route::get('/krs-aktif', [KrsController::class, 'aktif']);
        Route::get('/nilai-ringkas', [NilaiController::class, 'ringkas']);

        Route::post('/absensi/scan', [AbsensiController::class, 'scan']);

        Route::get('/tugas', [TugasController::class, 'index']);
        Route::post('/tugas/{id}/kumpul', [TugasController::class, 'kumpul']);

        Route::get('/tagihan', [TagihanController::class, 'index']);
        Route::post('/tagihan/{tagihan}/va', [TagihanController::class, 'createVa']);

        Route::get('/bimbingan', [BimbinganController::class, 'index']);
        Route::post('/bimbingan', [BimbinganController::class, 'store']);

        Route::get('/pengumuman', [PengumumanController::class, 'index']);

        Route::get('/notifikasi', [NotifikasiController::class, 'index']);
        Route::post('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'read']);
        Route::post('/notifikasi/token', [NotifikasiController::class, 'registerToken']);

        Route::post('/ai/chat', [AiController::class, 'chat'])->middleware('throttle:30,1');
        Route::get('/analitik/at-risk', [AiController::class, 'atRisk']);
        Route::get('/ai/rekomendasi', [AiController::class, 'rekomendasi']);
        Route::post('/ai/grade', [AiController::class, 'grade'])->middleware('throttle:30,1');
        Route::post('/ai/ringkas', [AiController::class, 'ringkas'])->middleware('throttle:30,1');
        Route::post('/ai/rag', [AiController::class, 'rag'])->middleware('throttle:30,1');
        Route::post('/ai/notulen', [AiController::class, 'notulen'])->middleware('throttle:30,1');
        Route::get('/analitik/anomali', [AiController::class, 'anomali']);
        Route::post('/device-token', [DeviceTokenController::class, 'store']);
    });
});

Route::prefix('v2')->group(function (): void {
    // API publik read-only (tanpa token, throttle ketat).
    Route::middleware('throttle:60,1')->group(function (): void {
        Route::get('/status', [PublikApiController::class, 'status']);
        Route::get('/prodi', [PublikApiController::class, 'prodi']);
        Route::get('/pengumuman', [PublikApiController::class, 'pengumuman']);
    });

    // Webhook masuk — keaslian via HMAC X-Webhook-Signature (secret di halaman Integrasi).
    Route::post('/webhook/terima', [PublikApiController::class, 'webhook'])->middleware('throttle:30,1');
});
