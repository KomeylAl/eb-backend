<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->timestamps();
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('companion_id')->nullable()->constrained('companions')->nullOnDelete();
            $table->foreignUuid('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_number')->unique();
            $table->string('reference_source')->nullable();
            $table->date('admission_date')->nullable();
            $table->date('visit_date')->nullable();
            $table->text('chief_complaints')->nullable();
            $table->text('present_illness')->nullable();
            $table->text('past_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('personal_history')->nullable();
            $table->text('mse')->nullable();
            $table->text('diagnosis')->nullable();
            $table->timestamps();
        });

        Schema::create('record_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_images');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('companions');
    }
};
