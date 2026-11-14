<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-040: placeholder digabung ke migrasi organisasi (tetap 1 file jalan). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organisasi_anggota')) {
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
    }

    public function down(): void
    {
        // Dicakup migrasi organisasi.
    }
};
