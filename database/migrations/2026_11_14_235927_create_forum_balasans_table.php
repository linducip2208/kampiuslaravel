<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-029: balasan thread (1 level, kutip via kolom opsional). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_balasan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_thread')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('isi');
            $table->timestamps();
            $table->index('thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_balasan');
    }
};
