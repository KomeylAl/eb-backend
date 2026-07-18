<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('national_code')->unique();
            $table->string('card_number')->nullable()->unique();
            $table->string('medical_number')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->json('days')->nullable();
            $table->json('times')->nullable();
            $table->string('profile_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
