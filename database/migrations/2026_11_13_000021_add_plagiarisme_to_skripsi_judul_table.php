<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skripsi_judul', function (Blueprint $table) {
            $table->foreignId('topik_id')->nullable()->after('pembimbing2_id')->index();
            $table->float('similarity')->nullable()->after('topik_id');
            $table->string('plagiarisme_status', 20)->nullable()->after('similarity');
            $table->text('plagiarisme_laporan')->nullable()->after('plagiarisme_status');
        });
    }

    public function down(): void
    {
        Schema::table('skripsi_judul', function (Blueprint $table) {
            $table->dropColumn(['topik_id', 'similarity', 'plagiarisme_status', 'plagiarisme_laporan']);
        });
    }
};
