<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-058: pemakaian + estimasi biaya AI per user per hari per jenis. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_kuota', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal');
            $table->string('jenis', 40);
            $table->unsignedInteger('jumlah')->default(0);
            $table->unsignedBigInteger('estimasi_biaya')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'tanggal', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_kuota');
    }
};
