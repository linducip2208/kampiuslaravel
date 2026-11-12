<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            // Relasi ke modul M1 (mahasiswa, tahun_akademik) tanpa hard FK (dibuat di migrasi lain).
            $table->foreignId('mahasiswa_id')->index();
            $table->foreignId('tahun_akademik_id')->nullable()->index();
            $table->enum('jenis', ['UKT', 'SPP', 'PMB', 'lain'])->default('UKT');
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('terbayar')->default(0);
            $table->enum('status', ['belum_bayar', 'sebagian', 'lunas'])->default('belum_bayar');
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'tahun_akademik_id', 'jenis'], 'tagihan_mhs_tahun_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
