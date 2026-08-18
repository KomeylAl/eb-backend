<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_certificate_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->unique()->constrained('workshops')->cascadeOnDelete();
            $table->string('template_key')->default('classic');
            $table->string('clinic_name')->nullable();
            $table->string('title')->nullable();
            $table->text('body_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_title')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });

        Schema::create('workshop_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workshop_id')->constrained('workshops')->cascadeOnDelete();
            $table->foreignUuid('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('template_key');
            $table->string('certificate_number')->unique();
            $table->timestamp('issued_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['workshop_id', 'participant_id']);
            $table->index(['workshop_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_certificates');
        Schema::dropIfExists('workshop_certificate_templates');
    }
};
