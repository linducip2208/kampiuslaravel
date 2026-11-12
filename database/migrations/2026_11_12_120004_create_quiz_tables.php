<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kelas_id')->index();
            $table->string('judul');
            $table->unsignedInteger('durasi_menit')->default(60);
            $table->timestamp('deadline')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_soal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id')->index();
            $table->text('pertanyaan');
            $table->string('tipe')->default('pg');
            $table->decimal('bobot', 5, 2)->default(10);
            $table->string('kunci_jawaban', 5)->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_jawaban', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_soal_id')->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->text('jawaban')->nullable();
            $table->decimal('skor', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['quiz_soal_id', 'mahasiswa_id'], 'uq_quiz_jawaban_soal_mhs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_jawaban');
        Schema::dropIfExists('quiz_soal');
        Schema::dropIfExists('quiz');
    }
};
