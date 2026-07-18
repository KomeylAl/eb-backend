<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('time');
            $table->unsignedInteger('amount')->default(0);
            $table->string('status'); // pending|done
            $table->timestamps();

            $table->index(['date', 'status']);
        });

        Schema::create('appointment_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('appointment_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
            $table->string('status'); // pending|paid|unpaid
            $table->unsignedInteger('amount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('appointment_user');
        Schema::dropIfExists('appointments');
    }
};
