<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skripsi_topik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->index();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('bidang')->nullable();
            $table->unsignedInteger('kuota')->default(1);
            $table->unsignedInteger('diambil')->default(0);
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skripsi_topik');
    }
};
