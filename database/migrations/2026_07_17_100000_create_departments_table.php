<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->string('thumbnail')->nullable();
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('department_doctor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['department_id', 'doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_doctor');
        Schema::dropIfExists('departments');
    }
};
