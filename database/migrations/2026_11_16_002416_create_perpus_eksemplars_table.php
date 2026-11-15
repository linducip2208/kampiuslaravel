<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Perpustakaan: eksemplar fisik dengan barcode unik. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpus_eksemplar', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buku_id')->constrained('perpus_buku')->cascadeOnDelete();
            $table->string('barcode', 60)->unique();
            $table->string('status', 20)->default('tersedia');
            $table->string('kondisi', 30)->nullable();
            $table->timestamps();
            $table->index(['buku_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perpus_eksemplar');
    }
};
