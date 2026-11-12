<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fakultas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->timestamps();
        });

        Schema::create('tahun_akademik', function (Blueprint $table) {
            $table->id();
            $table->string('tahun');
            $table->string('semester');
            $table->boolean('aktif')->default(false);
            $table->timestamps();
            $table->unique(['tahun', 'semester']);
        });

        Schema::create('ruang', function (Blueprint $table) {
            $table->id();
            $table->string('gedung');
            $table->string('nama');
            $table->unsignedInteger('kapasitas')->default(0);
            $table->timestamps();
        });

        Schema::create('prodi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fakultas_id')->constrained('fakultas')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jenjang');
            $table->string('kode')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodi');
        Schema::dropIfExists('ruang');
        Schema::dropIfExists('tahun_akademik');
        Schema::dropIfExists('fakultas');
    }
};
