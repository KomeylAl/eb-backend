<?php

use App\Enums\TreatmentProgramStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('status')->default(TreatmentProgramStatus::Active->value);
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'doctor_id']);
            $table->index(['doctor_id', 'status']);
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->foreignUuid('treatment_program_id')
                ->nullable()
                ->after('id')
                ->constrained('treatment_programs')
                ->cascadeOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignUuid('treatment_program_id')
                ->nullable()
                ->after('id')
                ->constrained('treatment_programs')
                ->nullOnDelete();
            $table->text('session_notes')->nullable()->after('status');
        });

        $this->migrateExistingData();

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropUnique(['client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treatment_program_id');
            $table->dropColumn('session_notes');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treatment_program_id');
            $table->unique('client_id');
        });

        Schema::dropIfExists('treatment_programs');
    }

    private function migrateExistingData(): void
    {
        $now = now();

        $records = DB::table('medical_records')->get();
        $programByPair = [];

        foreach ($records as $record) {
            $doctorId = $record->doctor_id;

            if (! $doctorId) {
                $doctorId = DB::table('appointment_user')
                    ->where('client_id', $record->client_id)
                    ->orderBy('created_at')
                    ->value('doctor_id');
            }

            if (! $doctorId) {
                // Skip orphan records without a resolvable doctor — leave treatment_program_id null until fixed.
                continue;
            }

            $pairKey = $record->client_id.'|'.$doctorId;
            if (! isset($programByPair[$pairKey])) {
                $programId = (string) Str::uuid();
                DB::table('treatment_programs')->insert([
                    'id' => $programId,
                    'client_id' => $record->client_id,
                    'doctor_id' => $doctorId,
                    'title' => 'برنامه درمان',
                    'status' => TreatmentProgramStatus::Active->value,
                    'started_at' => $record->admission_date ?? $now->toDateString(),
                    'ended_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $programByPair[$pairKey] = $programId;
            }

            DB::table('medical_records')
                ->where('id', $record->id)
                ->update(['treatment_program_id' => $programByPair[$pairKey]]);
        }

        $pairs = DB::table('appointment_user')
            ->select('client_id', 'doctor_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            $pairKey = $pair->client_id.'|'.$pair->doctor_id;
            if (! isset($programByPair[$pairKey])) {
                $programId = (string) Str::uuid();
                DB::table('treatment_programs')->insert([
                    'id' => $programId,
                    'client_id' => $pair->client_id,
                    'doctor_id' => $pair->doctor_id,
                    'title' => 'برنامه درمان',
                    'status' => TreatmentProgramStatus::Active->value,
                    'started_at' => $now->toDateString(),
                    'ended_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $programByPair[$pairKey] = $programId;
            }

            $appointmentIds = DB::table('appointment_user')
                ->where('client_id', $pair->client_id)
                ->where('doctor_id', $pair->doctor_id)
                ->pluck('appointment_id');

            DB::table('appointments')
                ->whereIn('id', $appointmentIds)
                ->whereNull('treatment_program_id')
                ->update(['treatment_program_id' => $programByPair[$pairKey]]);
        }
    }
};
