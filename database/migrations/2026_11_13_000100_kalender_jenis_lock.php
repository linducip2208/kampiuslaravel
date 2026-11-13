<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const JENIS_BARU = ['kuliah', 'uts', 'uas', 'libur', 'pembayaran', 'pmb', 'wisuda', 'krs', 'sidang', 'lain'];

    private const JENIS_LAMA = ['kuliah', 'uts', 'uas', 'libur', 'pembayaran', 'pmb', 'wisuda', 'lain'];

    public function up(): void
    {
        $this->gantiJenis(self::JENIS_BARU);
    }

    public function down(): void
    {
        $this->gantiJenis(self::JENIS_LAMA);
    }

    /**
     * @param  list<string>  $jenis
     */
    private function gantiJenis(array $jenis): void
    {
        if (! Schema::hasTable('kalender_events')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $daftar = "'".implode("','", $jenis)."'";
            DB::statement("ALTER TABLE `kalender_events` MODIFY `jenis` ENUM({$daftar}) NOT NULL DEFAULT 'lain'");

            return;
        }

        // SQLite menyimpan ENUM sebagai CHECK constraint: bangun ulang tabel.
        Schema::disableForeignKeyConstraints();

        Schema::create('kalender_events_baru', function (Blueprint $table) use ($jenis) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->string('judul');
            $table->enum('jenis', $jenis)->default('lain');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        $kolom = ['id', 'tahun_akademik_id', 'judul', 'jenis', 'tgl_mulai', 'tgl_selesai', 'deskripsi', 'created_at', 'updated_at'];
        DB::table('kalender_events_baru')->insertUsing(
            $kolom,
            DB::table('kalender_events')->select($kolom)
        );

        Schema::drop('kalender_events');
        Schema::rename('kalender_events_baru', 'kalender_events');

        Schema::enableForeignKeyConstraints();
    }
};
