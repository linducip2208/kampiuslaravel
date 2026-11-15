<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Perpustakaan: katalog buku (batch 6 ide bank). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpus_buku', function (Blueprint $table): void {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('judul', 255);
            $table->string('pengarang', 255)->nullable();
            $table->string('penerbit', 150)->nullable();
            $table->string('tahun', 9)->nullable();
            $table->string('isbn', 30)->nullable();
            $table->string('kategori', 60)->nullable();
            $table->timestamps();
            $table->index(['judul', 'pengarang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perpus_buku');
    }
};
