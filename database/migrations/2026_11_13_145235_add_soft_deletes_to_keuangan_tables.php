<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah soft delete ke tabel keuangan (aman: kolom nullable, tanpa hapus data).
     */
    public function up(): void
    {
        foreach (['tagihan', 'pembayaran', 'jurnal_keuangan'] as $tabel) {
            if (Schema::hasTable($tabel) && ! Schema::hasColumn($tabel, 'deleted_at')) {
                Schema::table($tabel, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['jurnal_keuangan', 'pembayaran', 'tagihan'] as $tabel) {
            if (Schema::hasTable($tabel) && Schema::hasColumn($tabel, 'deleted_at')) {
                Schema::table($tabel, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
