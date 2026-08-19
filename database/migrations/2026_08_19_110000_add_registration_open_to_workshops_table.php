<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->boolean('registration_open')->default(true)->after('img_path');
        });
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn('registration_open');
        });
    }
};
