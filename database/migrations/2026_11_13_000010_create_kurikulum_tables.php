<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurikulum versi + pemetaan MK + graf prasyarat/ekivalensi.
     * Kolom legacy matakuliah.prasyarat_id tetap dipertahankan
     * agar logika KRS yang ada tidak berubah.
     */
    public function up(): void
    {
        Schema::create('kurikulum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('kode', 30);
            $table->string('nama');
            $table->unsignedSmallInteger('tahun_berlaku');
            $table->string('status', 20)->default('aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['prodi_id', 'kode']);
        });

        Schema::create('kurikulum_mk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained('kurikulum')->cascadeOnDelete();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester')->default(1);
            $table->string('sifat', 20)->default('wajib');
            $table->timestamps();
            $table->unique(['kurikulum_id', 'matakuliah_id']);
        });

        Schema::create('mk_prasyarat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->foreignId('prasyarat_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->string('jenis', 20)->default('prasyarat');
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['matakuliah_id', 'prasyarat_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mk_prasyarat');
        Schema::dropIfExists('kurikulum_mk');
        Schema::dropIfExists('kurikulum');
    }
};
