<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            // Nomor SK kepengurusan (dibuat otomatis saat simpan bila kosong).
            $table->string('nomor_sk', 60)->nullable()->after('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisasi', function (Blueprint $table) {
            $table->dropColumn('nomor_sk');
        });
    }
};
