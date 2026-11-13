<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mbkm_mitra', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jenis', 30)->default('industri');
            $table->string('alamat')->nullable();
            $table->string('kontak')->nullable();
            $table->string('email')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('magang_lowongan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mbkm_mitra')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('kuota')->default(1);
            $table->date('deadline')->nullable();
            $table->string('status', 20)->default('buka');
            $table->timestamps();
        });

        Schema::create('magang_lamaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lowongan_id')->constrained('magang_lowongan')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('status', 20)->default('diajukan');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['lowongan_id', 'mahasiswa_id']);
        });

        Schema::create('mbkm_konversi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('mitra_id')->nullable()->constrained('mbkm_mitra')->nullOnDelete();
            $table->string('jenis_kegiatan', 40)->default('magang');
            $table->foreignId('matakuliah_id')->nullable()->constrained('matakuliah')->nullOnDelete();
            $table->unsignedTinyInteger('sks_diakui')->default(0);
            $table->string('huruf', 2)->nullable();
            $table->string('status', 20)->default('diajukan');
            $table->timestamps();
        });

        Schema::create('mbkm_logbook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('konversi_id')->constrained('mbkm_konversi')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('aktivitas');
            $table->string('bukti_path')->nullable();
            $table->string('status', 20)->default('diajukan');
            $table->timestamps();
        });

        Schema::create('wisuda_batch', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('periode', 20);
            $table->date('tanggal_wisuda')->nullable();
            $table->string('lokasi')->nullable();
            $table->unsignedInteger('kuota')->default(500);
            $table->string('status', 20)->default('dibuka');
            $table->timestamps();
            $table->unique(['nama', 'periode']);
        });

        Schema::create('wisuda_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('wisuda_batch')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('no_kursi')->nullable();
            $table->string('status_toga', 20)->default('belum');
            $table->string('no_ijazah')->nullable()->unique();
            $table->string('pin_ijazah', 30)->nullable();
            $table->string('status_skl', 20)->default('belum');
            $table->timestamps();
            $table->unique(['batch_id', 'mahasiswa_id']);
        });

        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswa')->cascadeOnDelete();
            $table->year('tahun_lulus');
            $table->string('pekerjaan')->nullable();
            $table->string('instansi')->nullable();
            $table->string('hp')->nullable();
            $table->boolean('bersedia_tracer')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
        Schema::dropIfExists('wisuda_peserta');
        Schema::dropIfExists('wisuda_batch');
        Schema::dropIfExists('mbkm_logbook');
        Schema::dropIfExists('mbkm_konversi');
        Schema::dropIfExists('magang_lamaran');
        Schema::dropIfExists('magang_lowongan');
        Schema::dropIfExists('mbkm_mitra');
    }
};
