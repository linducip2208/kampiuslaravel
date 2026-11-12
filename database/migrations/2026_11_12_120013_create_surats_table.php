<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->index();
            $table->enum('jenis', ['aktif_kuliah', 'pengantar_kp', 'keterangan_lulus', 'legalisir'])->default('aktif_kuliah');
            $table->text('keperluan')->nullable();
            $table->string('nomor_surat')->nullable()->unique();
            $table->enum('status', ['diajukan', 'diproses', 'selesai', 'ditolak'])->default('diajukan');
            $table->string('file_pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
