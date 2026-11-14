<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-049: pemetaan asesmen (soal kuis / tugas) ke CPMK. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpmk_asesmen', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cpmk_id')->constrained('cpmk')->cascadeOnDelete();
            $table->string('sumber_tipe', 20);
            $table->unsignedBigInteger('sumber_id');
            $table->decimal('bobot', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['cpmk_id', 'sumber_tipe', 'sumber_id']);
            $table->index(['sumber_tipe', 'sumber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpmk_asesmen');
    }
};
