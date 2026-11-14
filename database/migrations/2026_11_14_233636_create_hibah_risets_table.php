<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-042: hibah riset + pencairan bertahap. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hibah_riset', function (Blueprint $table): void {
            $table->id();
            $table->string('judul', 255);
            $table->string('skema', 100)->nullable();
            $table->string('sumber_dana', 100)->nullable();
            $table->string('tahun', 9);
            $table->unsignedBigInteger('total_dana')->default(0);
            $table->foreignId('ketua_dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('status', 20)->default('diajukan');
            $table->timestamps();
        });

        Schema::create('hibah_pencairan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hibah_id')->constrained('hibah_riset')->cascadeOnDelete();
            $table->unsignedInteger('tahap')->default(1);
            $table->unsignedBigInteger('nominal')->default(0);
            $table->date('tanggal')->nullable();
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hibah_pencairan');
        Schema::dropIfExists('hibah_riset');
    }
};
