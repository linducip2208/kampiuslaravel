<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-033: anggaran per tahun + kode jenis tagihan (untuk realisasi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggaran', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('kode', 60);
            $table->string('nama', 150);
            $table->unsignedBigInteger('pagu')->default(0);
            $table->string('keterangan', 500)->nullable();
            $table->timestamps();
            $table->unique(['tahun_akademik_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggaran');
    }
};
