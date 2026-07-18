<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('specialization')->nullable();
            $table->json('educations')->nullable();
            $table->json('experiences')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('social_links')->nullable();
            $table->longText('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type'); // link|file
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_resources');
        Schema::dropIfExists('resumes');
    }
};
