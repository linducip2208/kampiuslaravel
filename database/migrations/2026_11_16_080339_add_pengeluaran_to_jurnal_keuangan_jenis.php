<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const JENIS = ['penerimaan', 'refund', 'penyesuaian', 'pengeluaran'];

    /**
     * Tambah jenis 'pengeluaran' untuk jurnal kas keluar (bayar hutang).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `jurnal_keuangan` MODIFY `jenis` ENUM('penerimaan','refund','penyesuaian','pengeluaran') NOT NULL DEFAULT 'penerimaan'");

            return;
        }

        // SQLite: bangun ulang (data disalin utuh). Rename dulu, lalu
        // lepas index unik lama (nama index global di SQLite) sebelum create.
        DB::statement('PRAGMA foreign_keys=OFF');
        Schema::rename('jurnal_keuangan', 'jurnal_keuangan_lama');
        DB::statement('DROP INDEX IF EXISTS jurnal_bayar_jenis_unique');
        DB::statement('DROP INDEX IF EXISTS jurnal_keuangan_pembayaran_id_index');
        DB::statement('DROP INDEX IF EXISTS jurnal_keuangan_tagihan_id_index');
        DB::statement('DROP INDEX IF EXISTS jurnal_keuangan_created_by_index');
        Schema::create('jurnal_keuangan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->unsignedBigInteger('pembayaran_id')->nullable()->index();
            $table->unsignedBigInteger('tagihan_id')->nullable()->index();
            $table->string('jenis', 20)->default('penerimaan');
            $table->string('akun_debet', 60);
            $table->string('akun_kredit', 60);
            $table->unsignedBigInteger('nominal');
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['pembayaran_id', 'jenis'], 'jurnal_bayar_jenis_unique');
        });
        DB::statement('INSERT INTO jurnal_keuangan (id, tanggal, pembayaran_id, tagihan_id, jenis, akun_debet, akun_kredit, nominal, keterangan, created_by, created_at, updated_at, deleted_at) SELECT id, tanggal, pembayaran_id, tagihan_id, jenis, akun_debet, akun_kredit, nominal, keterangan, created_by, created_at, updated_at, deleted_at FROM jurnal_keuangan_lama');
        Schema::drop('jurnal_keuangan_lama');
        DB::statement('PRAGMA foreign_keys=ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('jurnal_keuangan')->where('jenis', 'pengeluaran')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `jurnal_keuangan` MODIFY `jenis` ENUM('penerimaan','refund','penyesuaian') NOT NULL DEFAULT 'penerimaan'");
        }
        // SQLite: CHECK tak diperketat ulang (kolom string tetap valid).
    }
};
