<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['treatment_program_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('treatment_program_id')
                ->references('id')
                ->on('treatment_programs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['treatment_program_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('treatment_program_id')
                ->references('id')
                ->on('treatment_programs')
                ->nullOnDelete();
        });
    }
};
