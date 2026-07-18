<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('admin_id')->constrained('users')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('abouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->longText('about');
            $table->string('phones')->nullable();
            $table->string('mobile_phones')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('file_path');
            $table->string('file_url')->nullable();
            $table->timestamps();
        });

        Schema::create('restores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restores');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('abouts');
        Schema::dropIfExists('invoices');
    }
};
