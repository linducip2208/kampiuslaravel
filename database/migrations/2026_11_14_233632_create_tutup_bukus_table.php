<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M-032: tutup-buku periode + buka-terkendali.
 * Jurnal/refund ditolak bila periode tagihan berstatus tutup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutup_buku', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tahun_akademik_id')->unique()->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('status', 10)->default('buka');
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditutup_at')->nullable();
            $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibuka_at')->nullable();
            $table->string('catatan', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutup_buku');
    }
};
