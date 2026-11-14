<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-039: lamaran ke lowongan (1 email per lowongan). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lamaran_kerja', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lowongan_id')->constrained('lowongan_kerja')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('email', 150);
            $table->string('hp', 30)->nullable();
            $table->string('berkas_path', 255)->nullable();
            $table->string('status', 20)->default('masuk');
            $table->timestamps();
            $table->unique(['lowongan_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lamaran_kerja');
    }
};
