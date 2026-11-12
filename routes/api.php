<?php

use App\Http\Controllers\Api\V1\AbsensiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BimbinganController;
use App\Http\Controllers\Api\V1\JadwalController;
use App\Http\Controllers\Api\V1\KrsController;
use App\Http\Controllers\Api\V1\NilaiController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\PengumumanController;
use App\Http\Controllers\Api\V1\TagihanController;
use App\Http\Controllers\Api\V1\TugasController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
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
    });
});
