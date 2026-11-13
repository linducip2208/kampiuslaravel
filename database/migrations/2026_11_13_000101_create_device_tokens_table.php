<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 500)->unique();
            $table->string('platform', 20)->default('android');
            $table->string('topic', 100)->nullable()->index();
            $table->string('app_version', 30)->nullable();
            $table->timestamps();
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
        Schema::dropIfExists('device_tokens');
    }
};
