<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ukt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->string('angkatan', 4)->nullable();
            $table->string('jalur', 30)->nullable();
            $table->string('jenis', 20)->default('UKT');
            $table->unsignedBigInteger('nominal');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('denda_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->string('jenis', 20)->default('UKT');
            $table->unsignedInteger('terlambat_hari_min')->default(1);
            $table->enum('mode', ['persen', 'nominal'])->default('persen');
            $table->unsignedInteger('nilai')->default(0);
            $table->unsignedBigInteger('maksimal')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('tagihan_cicilan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->unsignedBigInteger('nominal');
            $table->unsignedBigInteger('terbayar')->default(0);
            $table->date('jatuh_tempo')->nullable();
            $table->enum('status', ['belum_bayar', 'sebagian', 'lunas'])->default('belum_bayar');
            $table->timestamps();
            $table->unique(['tagihan_id', 'urutan']);
        });

        if (Schema::hasTable('mahasiswa') && ! Schema::hasColumn('mahasiswa', 'jalur')) {
            Schema::table('mahasiswa', function (Blueprint $table) {
                $table->string('jalur', 30)->nullable()->after('angkatan');
            });
        }

        if (Schema::hasTable('tagihan')) {
            Schema::table('tagihan', function (Blueprint $table) {
                if (! Schema::hasColumn('tagihan', 'jatuh_tempo')) {
                    $table->date('jatuh_tempo')->nullable()->after('terbayar');
                }
                if (! Schema::hasColumn('tagihan', 'denda')) {
                    $table->unsignedBigInteger('denda')->default(0)->after('jatuh_tempo');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_cicilan');
        Schema::dropIfExists('denda_rules');
        Schema::dropIfExists('ukt_templates');

        if (Schema::hasTable('mahasiswa') && Schema::hasColumn('mahasiswa', 'jalur')) {
            Schema::table('mahasiswa', function (Blueprint $table) {
                $table->dropColumn('jalur');
            });
        }

        if (Schema::hasTable('tagihan')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('tagihan', 'jatuh_tempo')) {
                    $columns[] = 'jatuh_tempo';
                }
                if (Schema::hasColumn('tagihan', 'denda')) {
                    $columns[] = 'denda';
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
