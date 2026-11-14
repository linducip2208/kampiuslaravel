<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-049: CPMK per MK memetakan ke CPL (dengan bobot kontribusi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpmk', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->foreignId('cpl_id')->constrained('cpl')->cascadeOnDelete();
            $table->string('kode', 20);
            $table->text('deskripsi');
            $table->decimal('bobot', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['matakuliah_id', 'kode']);
            $table->index('cpl_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpmk');
    }
};
