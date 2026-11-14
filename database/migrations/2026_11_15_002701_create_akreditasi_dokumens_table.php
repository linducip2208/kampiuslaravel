<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-050: bukti akreditasi per prodi (borang + verifikasi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akreditasi_dokumen', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('kategori', 60);
            $table->string('judul', 255);
            $table->string('tautan', 500)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();
            $table->index(['prodi_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akreditasi_dokumen');
    }
};
