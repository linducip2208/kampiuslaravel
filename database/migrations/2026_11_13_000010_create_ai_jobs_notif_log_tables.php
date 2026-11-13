<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis', 40); // chat, ringkas, quiz, grade, transkrip, rekomendasi, at_risk, anomali
            $table->text('input')->nullable();
            $table->longText('output')->nullable();
            $table->string('status', 20)->default('selesai'); // antre, proses, selesai, gagal
            $table->string('provider', 40)->default('simulated');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'jenis']);
        });

        Schema::create('notif_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notifikasi_id')->nullable()->constrained('notifikasi')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 20)->default('db'); // db, fcm
            $table->string('status', 20)->default('terkirim');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notif_log');
        Schema::dropIfExists('ai_jobs');
    }
};
