<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skripsi_judul', function (Blueprint $table) {
            $table->id();
            // Relasi ke modul M1 (mahasiswa, dosen) tanpa hard FK (dibuat di migrasi lain).
            $table->foreignId('mahasiswa_id')->index();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'revisi', 'sidang', 'lulus'])->default('diajukan');
            $table->foreignId('pembimbing1_id')->nullable()->index();
            $table->foreignId('pembimbing2_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skripsi_judul');
    }
};
