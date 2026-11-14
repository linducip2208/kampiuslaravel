<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-044: pengabdian masyarakat dosen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengabdian', function (Blueprint $table): void {
            $table->id();
            $table->string('judul', 255);
            $table->string('lokasi', 150)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->foreignId('ketua_dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->unsignedBigInteger('dana')->default(0);
            $table->string('status', 20)->default('rencana');
            $table->string('laporan_ringkas', 1000)->nullable();
            $table->timestamps();
            $table->index(['ketua_dosen_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengabdian');
    }
};
