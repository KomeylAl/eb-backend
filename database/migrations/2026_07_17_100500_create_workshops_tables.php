<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('organizers')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('week_day')->nullable();
            $table->string('time')->nullable();
            $table->string('img_path')->nullable();
            $table->timestamps();
        });

        Schema::create('workshop_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('session_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('english_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('national_code')->nullable()->unique();
            $table->string('gender')->nullable();
            $table->timestamps();
        });

        Schema::create('participant_workshop', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'workshop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_workshop');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('workshop_sessions');
        Schema::dropIfExists('workshops');
    }
};
