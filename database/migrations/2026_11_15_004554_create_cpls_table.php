<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-049: CPL per prodi (Capaian Pembelajaran Lulusan). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpl', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->string('kode', 20);
            $table->text('deskripsi');
            $table->timestamps();
            $table->unique(['prodi_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpl');
    }
};
