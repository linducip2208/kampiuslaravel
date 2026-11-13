<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bimbingan', function (Blueprint $table) {
            $table->unsignedInteger('versi')->default(1)->after('file_path');
            $table->foreignId('parent_id')->nullable()->after('versi')->index();
        });
    }

    public function down(): void
    {
        Schema::table('bimbingan', function (Blueprint $table) {
            $table->dropColumn(['versi', 'parent_id']);
        });
    }
};
