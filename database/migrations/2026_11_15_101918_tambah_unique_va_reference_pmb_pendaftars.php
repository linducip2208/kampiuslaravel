<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P8 (tindak lanjut M-013): va_reference pendaftar WAJIB unik.
 * settleByReference() mengandalkan 1 baris per reference — tanpa unique,
 * duplikat data bisa melunasi pendaftar yang salah.
 * Migrasi aman: cek duplikat dulu (gagal eksplisit bila ada), lalu
 * buat unique index bila belum ada (nullable tetap boleh banyak NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pmb_pendaftars') || ! Schema::hasColumn('pmb_pendaftars', 'va_reference')) {
            return;
        }

        $duplikat = 0;

        try {
            $duplikat = (int) DB::table('pmb_pendaftars as a')
                ->whereNotNull('a.va_reference')
                ->whereExists(function ($q): void {
                    $q->select(DB::raw(1))->from('pmb_pendaftars as b')
                        ->whereColumn('b.va_reference', 'a.va_reference')
                        ->whereColumn('b.id', '>', 'a.id');
                })
                ->count();
        } catch (Throwable) {
            return;
        }

        if ($duplikat > 0) {
            throw new RuntimeException("Ditemukan {$duplikat} va_reference ganda di pmb_pendaftars — rapikan data dulu sebelum migrasi.");
        }

        try {
            Schema::table('pmb_pendaftars', function (Blueprint $table): void {
                $table->unique('va_reference', 'pmb_va_reference_unique');
            });
        } catch (Throwable) {
            // Index sudah ada (rerun) — aman diabaikan.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pmb_pendaftars')) {
            return;
        }

        try {
            Schema::table('pmb_pendaftars', function (Blueprint $table): void {
                $table->dropUnique('pmb_va_reference_unique');
            });
        } catch (Throwable) {
        }
    }
};
