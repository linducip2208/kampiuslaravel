<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repository', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->index();
            $table->string('judul');
            $table->text('abstrak')->nullable();
            $table->string('file_path');
            $table->string('tahun', 4)->nullable();
            $table->boolean('embargo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repository');
    }
};
