<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-036: penyusutan garis lurus — umur manfaat + akumulasi + bulan terakhir. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->unsignedInteger('umur_manfaat_bulan')->default(48)->after('nilai');
            $table->unsignedBigInteger('akumulasi_penyusutan')->default(0)->after('umur_manfaat_bulan');
            $table->date('terakhir_susut_at')->nullable()->after('akumulasi_penyusutan');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->dropColumn(['umur_manfaat_bulan', 'akumulasi_penyusutan', 'terakhir_susut_at']);
        });
    }
};
