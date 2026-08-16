<?php

namespace App\Actions\MedicalRecord;

use App\Models\Companion;
use App\Models\MedicalRecord;
use App\Models\RecordImage;
use App\Models\TreatmentProgram;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpsertMedicalRecordAction
{
    public function __construct(private FileService $files) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function execute(TreatmentProgram $program, array $data, array $images = []): MedicalRecord
    {
        return DB::transaction(function () use ($program, $data, $images) {
            $companionId = null;

            if (! empty($data['companion_name'])) {
                $companionPayload = [
                    'name' => $data['companion_name'],
                    'phone' => $data['companion_phone'] ?? null,
                    'address' => $data['companion_address'] ?? null,
                    'birth_date' => $data['companion_birth_date'] ?? null,
                ];

                if (! empty($data['companion_phone'])) {
                    $companion = Companion::query()->updateOrCreate(
                        ['phone' => $data['companion_phone']],
                        $companionPayload,
                    );
                } else {
                    $companion = Companion::query()->create($companionPayload);
                }

                $companionId = $companion->id;
            }

            $payload = collect($data)->only([
                'supervisor_id',
                'admin_id',
                'record_number',
                'reference_source',
                'admission_date',
                'visit_date',
                'chief_complaints',
                'present_illness',
                'past_history',
                'family_history',
                'personal_history',
                'mse',
                'diagnosis',
            ])->all();

            if (! empty($data['doctor_id']) && $data['doctor_id'] !== $program->doctor_id) {
                $program->update(['doctor_id' => $data['doctor_id']]);
                $program->refresh();
            }

            $payload['treatment_program_id'] = $program->id;
            $payload['client_id'] = $program->client_id;
            $payload['doctor_id'] = $program->doctor_id;

            if ($companionId !== null) {
                $payload['companion_id'] = $companionId;
            }

            $record = MedicalRecord::query()->updateOrCreate(
                ['treatment_program_id' => $program->id],
                $payload,
            );

            $this->storeImages($record, $program->client_id, $images);

            return $record->load([
                'treatmentProgram',
                'client',
                'companion',
                'doctor',
                'supervisor',
                'admin',
                'images',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function executeClinical(
        TreatmentProgram $program,
        User $doctor,
        array $data,
        array $images = [],
    ): MedicalRecord {
        return DB::transaction(function () use ($program, $doctor, $data, $images) {
            $payload = collect($data)->only([
                'visit_date',
                'chief_complaints',
                'present_illness',
                'past_history',
                'family_history',
                'personal_history',
                'mse',
                'diagnosis',
            ])->all();

            $record = MedicalRecord::query()
                ->where('treatment_program_id', $program->id)
                ->first();

            if ($record === null) {
                $payload['treatment_program_id'] = $program->id;
                $payload['client_id'] = $program->client_id;
                $payload['doctor_id'] = $doctor->id;
                $payload['record_number'] = sprintf(
                    'DR-%s-%s',
                    Str::upper(Str::substr(str_replace('-', '', $program->id), 0, 8)),
                    now()->format('YmdHis'),
                );
                $record = MedicalRecord::query()->create($payload);
            } else {
                $record->update($payload);
            }

            $this->storeImages($record, $program->client_id, $images);

            return $record->fresh()->load([
                'treatmentProgram',
                'client',
                'companion',
                'doctor',
                'supervisor',
                'admin',
                'images',
            ]);
        });
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function storeImages(MedicalRecord $record, string $clientId, array $images): void
    {
        foreach ($images as $image) {
            $media = $this->files->store(
                $image,
                'medical_records',
                ['client_id' => $clientId],
            );
            RecordImage::query()->create([
                'medical_record_id' => $record->id,
                'file_path' => $media->path,
            ]);
        }
    }
}
