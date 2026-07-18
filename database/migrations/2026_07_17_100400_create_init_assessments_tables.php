<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('init_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date')->nullable();
            $table->string('time')->nullable();
            $table->string('status'); // pending|done
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('init_assessment_id')->constrained('init_assessments')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('init_assessment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_user');
        Schema::dropIfExists('init_assessments');
    }
};
