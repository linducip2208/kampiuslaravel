<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah jenis 'asrama' pada enum tagihan.jenis.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tagihan` MODIFY `jenis` ENUM('UKT','SPP','PMB','asrama','lain') NOT NULL DEFAULT 'UKT'");

            return;
        }

        // SQLite: enum dipetakan ke varchar tanpa CHECK — 'asrama' langsung
        // valid tanpa rebuild. (Rebuild via rename justru merusak FK tabel
        // lain yang menunjuk ke nama lama — jangan dilakukan.)
        if (! Schema::hasColumn('tagihan', 'jenis')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->string('jenis', 20)->default('UKT');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tagihan')->where('jenis', 'asrama')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tagihan` MODIFY `jenis` ENUM('UKT','SPP','PMB','lain') NOT NULL DEFAULT 'UKT'");
        }
    }
};
