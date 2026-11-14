<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Samakan tagihan_cicilan dengan tabel keuangan lain (soft deletes).
     * Aditif, guarded — pola add_soft_deletes_to_keuangan_tables.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tagihan_cicilan') || Schema::hasColumn('tagihan_cicilan', 'deleted_at')) {
            return;
        }
        Schema::table('tagihan_cicilan', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tagihan_cicilan') || ! Schema::hasColumn('tagihan_cicilan', 'deleted_at')) {
            return;
        }
        Schema::table('tagihan_cicilan', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
