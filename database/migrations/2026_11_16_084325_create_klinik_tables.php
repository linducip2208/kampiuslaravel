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
        Schema::create('klinik_kunjungan', function (Blueprint $table) {
            // Rekam medis sederhana: keluhan → diagnosa + tindakan.
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('keluhan');
            $table->text('diagnosa')->nullable();
            $table->text('tindakan')->nullable();
            $table->foreignId('pemeriksa_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['mahasiswa_id', 'tanggal']);
        });

        Schema::create('klinik_resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('klinik_kunjungan')->cascadeOnDelete();
            $table->string('obat', 150);
            $table->string('dosis', 100)->nullable();
            $table->string('jumlah', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klinik_resep');
        Schema::dropIfExists('klinik_kunjungan');
    }
};
