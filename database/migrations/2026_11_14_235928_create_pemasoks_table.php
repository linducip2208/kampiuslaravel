<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-034: pemasok (utang usaha) — pasangan AR mahasiswa di sisi AP. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemasok', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 150);
            $table->string('kontak', 100)->nullable();
            $table->string('rekening', 100)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemasok');
    }
};
