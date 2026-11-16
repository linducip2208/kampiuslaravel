<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sidang_penguji', function (Blueprint $table) {
            // Penguji eksternal: dosen_id boleh kosong bila nama diisi.
            $table->string('nama_eksternal', 150)->nullable()->after('dosen_id');
        });

        if (DB::getDriverName() === 'mysql') {
            // Longgarkan NOT NULL dosen_id.
            DB::statement('ALTER TABLE `sidang_penguji` MODIFY `dosen_id` BIGINT UNSIGNED NULL');
        } else {
            // SQLite tidak dukung MODIFY: bangun ulang tabel dengan
            // dosen_id nullable (data disalin utuh).
            DB::statement('PRAGMA foreign_keys=OFF');
            Schema::create('sidang_penguji_baru', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sidang_id')->index();
                $table->unsignedBigInteger('dosen_id')->nullable()->index();
                $table->string('peran', 30)->default('penguji');
                $table->decimal('nilai', 5, 2)->nullable();
                $table->string('nama_eksternal', 150)->nullable();
                $table->timestamps();
                $table->unique(['sidang_id', 'dosen_id']);
            });
            DB::statement('INSERT INTO sidang_penguji_baru (id, sidang_id, dosen_id, peran, nilai, nama_eksternal, created_at, updated_at) SELECT id, sidang_id, dosen_id, peran, nilai, nama_eksternal, created_at, updated_at FROM sidang_penguji');
            Schema::drop('sidang_penguji');
            Schema::rename('sidang_penguji_baru', 'sidang_penguji');
            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidang_penguji', function (Blueprint $table) {
            $table->dropColumn('nama_eksternal');
        });
    }
};
