<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-051: lapisan CBT di atas mesin kuis — mode, token masuk, acak soal, urutan per attempt. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz', function (Blueprint $table): void {
            $table->string('mode', 10)->default('latihan')->after('judul');
            $table->string('token', 12)->nullable()->after('mode');
            $table->boolean('acak_soal')->default(false)->after('token');
        });

        Schema::table('quiz_attempt', function (Blueprint $table): void {
            $table->json('urutan_soal')->nullable()->after('mahasiswa_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempt', function (Blueprint $table): void {
            $table->dropColumn('urutan_soal');
        });

        Schema::table('quiz', function (Blueprint $table): void {
            $table->dropColumn(['mode', 'token', 'acak_soal']);
        });
    }
};
