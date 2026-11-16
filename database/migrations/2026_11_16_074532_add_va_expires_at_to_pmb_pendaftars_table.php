<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pmb_pendaftars', function (Blueprint $table) {
            // Kedaluwarsa VA: issue baru beri +7 hari; settle menolak lewat tempo.
            $table->timestamp('va_expires_at')->nullable()->after('va_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pmb_pendaftars', function (Blueprint $table) {
            $table->dropColumn('va_expires_at');
        });
    }
};
