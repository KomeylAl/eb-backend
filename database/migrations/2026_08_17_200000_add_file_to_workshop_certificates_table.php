<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_certificates', function (Blueprint $table) {
            $table->string('source')->default('generated')->after('participant_id'); // generated|uploaded
            $table->string('template_key')->nullable()->change();
            $table->string('file_path')->nullable()->after('payload');
            $table->string('original_name')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('workshop_certificates', function (Blueprint $table) {
            $table->dropColumn(['source', 'file_path', 'original_name']);
            $table->string('template_key')->nullable(false)->change();
        });
    }
};
