<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-057: dokumen knowledge-base RAG (di-chunk saat simpan). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base', function (Blueprint $table): void {
            $table->id();
            $table->string('judul', 255);
            $table->text('konten');
            $table->string('sumber', 255)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('knowledge_chunk', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kb_id')->constrained('knowledge_base')->cascadeOnDelete();
            $table->unsignedInteger('urutan')->default(0);
            $table->text('teks');
            $table->timestamps();
            $table->index(['kb_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunk');
        Schema::dropIfExists('knowledge_base');
    }
};
