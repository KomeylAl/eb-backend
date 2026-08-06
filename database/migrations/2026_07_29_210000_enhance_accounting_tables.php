<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('service')->nullable()->after('amount');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('paid_amount')->default(0)->after('amount');
            $table->string('method')->nullable()->after('paid_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('file_path');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->unsignedInteger('old_paid_amount')->nullable();
            $table->unsignedInteger('new_paid_amount')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'created_at']);
        });

        Schema::create('manual_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->string('file_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });

        Schema::create('manual_invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('manual_invoice_id')->constrained('manual_invoices')->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('line_total')->default(0);
            $table->timestamps();
        });

        Schema::create('financial_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('manual_invoice_id')->nullable()->constrained('manual_invoices')->nullOnDelete();
            $table->string('type');
            $table->unsignedInteger('amount');
            $table->string('reason')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['client_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_adjustments');
        Schema::dropIfExists('manual_invoice_items');
        Schema::dropIfExists('manual_invoices');
        Schema::dropIfExists('payment_transactions');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'method']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
