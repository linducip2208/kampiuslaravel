<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marketing PMB: referral sekolah, VA biaya pendaftaran,
     * beasiswa PMB, follow-up WA, dan SEO prodi.
     */
    public function up(): void
    {
        Schema::create('pmb_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('sekolah', 150);
            $table->string('pic_nama', 100)->nullable();
            $table->string('pic_hp', 20)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::table('prodi', function (Blueprint $table) {
            if (! Schema::hasColumn('prodi', 'deskripsi')) {
                $table->text('deskripsi')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('prodi', 'seo_title')) {
                $table->string('seo_title', 160)->nullable()->after('deskripsi');
            }
            if (! Schema::hasColumn('prodi', 'seo_description')) {
                $table->string('seo_description', 200)->nullable()->after('seo_title');
            }
        });

        Schema::table('pmb_pendaftars', function (Blueprint $table) {
            if (! Schema::hasColumn('pmb_pendaftars', 'referral_kode')) {
                $table->string('referral_kode', 30)->nullable()->after('sekolah_asal')->index();
            }
            if (! Schema::hasColumn('pmb_pendaftars', 'beasiswa_id')) {
                $table->foreignId('beasiswa_id')->nullable()->after('gelombang_id')->index();
            }
            if (! Schema::hasColumn('pmb_pendaftars', 'va_reference')) {
                $table->string('va_reference', 60)->nullable()->after('status')->index();
            }
            if (! Schema::hasColumn('pmb_pendaftars', 'va_number')) {
                $table->string('va_number', 30)->nullable()->after('va_reference');
            }
            if (! Schema::hasColumn('pmb_pendaftars', 'bayar_status')) {
                $table->string('bayar_status', 20)->default('belum_bayar')->after('va_number')->index();
            }
            if (! Schema::hasColumn('pmb_pendaftars', 'last_follow_up_at')) {
                $table->timestamp('last_follow_up_at')->nullable()->after('catatan_verifikasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pmb_pendaftars', function (Blueprint $table) {
            foreach (['referral_kode', 'beasiswa_id', 'va_reference', 'va_number', 'bayar_status', 'last_follow_up_at'] as $col) {
                if (Schema::hasColumn('pmb_pendaftars', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('prodi', function (Blueprint $table) {
            foreach (['deskripsi', 'seo_title', 'seo_description'] as $col) {
                if (Schema::hasColumn('prodi', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('pmb_referrals');
    }
};
