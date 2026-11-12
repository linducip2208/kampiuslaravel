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
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nidn')->unique();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('status')->default('aktif');
            $table->timestamps();
        });

        Schema::create('matakuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->unsignedTinyInteger('sks')->default(2);
            $table->unsignedTinyInteger('semester')->default(1);
            $table->foreignId('prasyarat_id')->nullable()->constrained('matakuliah')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedInteger('kuota')->default(40);
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('hari')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->foreignId('ruang_id')->nullable()->constrained('ruang')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->year('angkatan');
            $table->foreignId('dosen_pa_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('matakuliah');
        Schema::dropIfExists('dosen');
    }
};
