<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('financial_adjustments', 'manual_invoice_id')) {
            Schema::table('financial_adjustments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('manual_invoice_id');
            });
        }

        Schema::dropIfExists('manual_invoice_items');
        Schema::dropIfExists('manual_invoices');

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'file_path')) {
                $table->dropColumn('file_path');
            }
            if (Schema::hasColumn('invoices', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'number')) {
                $table->string('number')->nullable()->after('admin_id');
            }
            if (! Schema::hasColumn('invoices', 'status')) {
                $table->string('status')->default('draft')->after('number');
            }
            if (! Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('issue_date');
            }
            if (! Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('invoices', 'subtotal')) {
                $table->unsignedInteger('subtotal')->default(0)->after('notes');
            }
            if (! Schema::hasColumn('invoices', 'total')) {
                $table->unsignedInteger('total')->default(0)->after('subtotal');
            }
        });

        foreach (DB::table('invoices')->get() as $invoice) {
            $updates = [];

            if (blank($invoice->number ?? null)) {
                $updates['number'] = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }

            if (blank($invoice->issue_date ?? null)) {
                $updates['issue_date'] = $invoice->from_date ?? now()->toDateString();
            }

            if (blank($invoice->status ?? null)) {
                $updates['status'] = 'draft';
            }

            if ($updates !== []) {
                DB::table('invoices')->where('id', $invoice->id)->update($updates);
            }
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('number')->nullable(false)->change();
            $table->date('issue_date')->nullable(false)->change();
            $table->date('from_date')->nullable()->change();
            $table->date('to_date')->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $sm = Schema::getConnection()->getSchemaBuilder();
            $indexes = collect($sm->getIndexes('invoices'))->pluck('name');

            if (! $indexes->contains('invoices_number_unique')) {
                $table->unique('number');
            }

            if (! $indexes->contains('invoices_client_id_status_index')) {
                $table->index(['client_id', 'status']);
            }
        });

        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
                $table->string('description');
                $table->string('unit')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedInteger('unit_price')->default(0);
                $table->unsignedInteger('line_total')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('financial_adjustments', 'invoice_id')) {
            Schema::table('financial_adjustments', function (Blueprint $table) {
                $table->foreignUuid('invoice_id')->nullable()->after('appointment_id')->constrained('invoices')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_adjustments', 'invoice_id')) {
            Schema::table('financial_adjustments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_id');
            });
        }

        Schema::dropIfExists('invoice_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropColumn([
                'number',
                'status',
                'issue_date',
                'due_date',
                'notes',
                'subtotal',
                'total',
            ]);
            $table->string('file_path')->nullable();
            $table->string('pdf_path')->nullable();
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

        Schema::table('financial_adjustments', function (Blueprint $table) {
            $table->foreignUuid('manual_invoice_id')->nullable()->constrained('manual_invoices')->nullOnDelete();
        });
    }
};
