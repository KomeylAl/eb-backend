<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->index('parent_id');
        });

        // Self-FK must be added after the table (and its PK) exists.
        // PostgreSQL rejects a same-blueprint constrained() + unique() combo.
        Schema::table('media_folders', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('media_folders')
                ->nullOnDelete();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('disk');
            $table->string('path');
            $table->string('collection');
            $table->foreignUuid('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('original_name');
            $table->string('name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('visibility')->default('public');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['collection', 'created_at']);
            $table->index('folder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_folders');
    }
};
