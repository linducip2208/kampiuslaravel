<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-039: lowongan kerja dosen/tendik. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lowongan_kerja', function (Blueprint $table): void {
            $table->id();
            $table->string('judul', 255);
            $table->string('unit', 100)->nullable();
            $table->string('jenis', 20)->default('tendik');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('kuota')->default(1);
            $table->date('tgl_tutup')->nullable();
            $table->string('status', 20)->default('buka');
            $table->timestamps();
            $table->index(['status', 'tgl_tutup']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lowongan_kerja');
    }
};
