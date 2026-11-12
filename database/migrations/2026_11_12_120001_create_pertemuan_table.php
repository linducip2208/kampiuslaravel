<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertemuan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kelas_id')->index();
            $table->unsignedInteger('ke');
            $table->date('tanggal')->nullable();
            $table->string('topik')->nullable();
            $table->string('qr_token', 64)->unique();
            $table->timestamp('qr_expires_at')->nullable();
            $table->timestamp('window_mulai')->nullable();
            $table->timestamp('window_selesai')->nullable();
            $table->string('pin', 10)->nullable();
            $table->timestamps();
            $table->unique(['kelas_id', 'ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertemuan');
    }
};
