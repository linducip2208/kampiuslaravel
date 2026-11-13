<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->index();
            $table->foreignId('mahasiswa_id')->index();
            // menunggu/dipromosikan/dibatalkan
            $table->string('status', 20)->default('menunggu')->index();
            $table->timestamps();

            $table->unique(['kelas_id', 'mahasiswa_id']);
        });

        if (Schema::hasTable('kelas')) {
            Schema::table('kelas_waitlist', function (Blueprint $table) {
                $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('mahasiswa')) {
            Schema::table('kelas_waitlist', function (Blueprint $table) {
                $table->foreign('mahasiswa_id')->references('id')->on('mahasiswa')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_waitlist');
    }
};
