<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-028: opsi PG tersimpan per soal (hasil impor bank soal). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_soal', function (Blueprint $table): void {
            $table->json('opsi')->nullable()->after('pertanyaan');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_soal', function (Blueprint $table): void {
            $table->dropColumn('opsi');
        });
    }
};
