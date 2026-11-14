<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-043: publikasi & HKI dosen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publikasi', function (Blueprint $table): void {
            $table->id();
            $table->string('judul', 255);
            $table->string('jenis', 20)->default('jurnal');
            $table->string('penulis', 500)->nullable();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->string('penerbit', 150)->nullable();
            $table->string('tahun', 9);
            $table->string('doi', 150)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->index(['dosen_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publikasi');
    }
};
