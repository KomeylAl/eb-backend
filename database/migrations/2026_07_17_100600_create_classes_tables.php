<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('week_day')->nullable();
            $table->string('time')->nullable();
            $table->timestamps();
        });

        Schema::create('class_dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('class_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // teacher|student
            $table->timestamps();

            $table->unique(['class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_user');
        Schema::dropIfExists('class_dates');
        Schema::dropIfExists('classes');
    }
};
