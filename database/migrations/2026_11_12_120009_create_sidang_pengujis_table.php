<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidang_penguji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->constrained('sidang')->cascadeOnDelete();
            $table->foreignId('dosen_id')->index();
            $table->string('peran', 30)->default('penguji');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['sidang_id', 'dosen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidang_penguji');
    }
};
