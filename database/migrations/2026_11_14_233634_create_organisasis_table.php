<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-040: organisasi kemahasiswaan (BEM/HIMA/UKM/komunitas) + keanggotaan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisasi', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 150);
            $table->string('jenis', 20)->default('ukm');
            $table->string('deskripsi', 500)->nullable();
            $table->foreignId('pembina_dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('organisasi_anggota', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organisasi_id')->constrained('organisasi')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('peran', 30)->default('anggota');
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
            $table->unique(['organisasi_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisasi_anggota');
        Schema::dropIfExists('organisasi');
    }
};
