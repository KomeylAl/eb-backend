<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('type')->default('system');
            $table->nullableUuidMorphs('notifiable');
            $table->string('priority')->default('normal');
            $table->json('delivery_channels')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_id')->constrained('app_notifications')->cascadeOnDelete();
            $table->uuidMorphs('receiver');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'receiver_id', 'receiver_type'], 'notification_receiver_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('app_notifications');
    }
};
