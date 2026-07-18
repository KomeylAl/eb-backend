<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('profile_path');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
