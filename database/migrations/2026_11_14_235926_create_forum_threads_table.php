<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-029: forum diskusi per kelas (terkunci oleh dosen bila selesai). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_thread', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 255);
            $table->text('isi');
            $table->boolean('terkunci')->default(false);
            $table->boolean('disematkan')->default(false);
            $table->timestamps();
            $table->index(['kelas_id', 'disematkan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_thread');
    }
};
