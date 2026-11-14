<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-028: sesi pengerjaan kuis (timer + recovery + nilai akhir). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempt', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quiz')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->timestamp('mulai_at');
            $table->timestamp('selesai_at')->nullable();
            $table->string('status', 20)->default('berjalan');
            $table->decimal('total_skor', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt');
    }
};
