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
        Schema::create('spmi_siklus', function (Blueprint $table): void {
            // Siklus PPEMPMTI per prodi+tahun: 5 tahap berurutan + status.
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->year('tahun');
            $table->string('tahap', 20);
            $table->string('status', 20)->default('belum');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['prodi_id', 'tahun', 'tahap']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spmi_siklus');
    }
};
