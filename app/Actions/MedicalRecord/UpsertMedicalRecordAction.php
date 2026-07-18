<?php

namespace App\Actions\MedicalRecord;

use App\Models\Companion;
use App\Models\MedicalRecord;
use App\Models\RecordImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpsertMedicalRecordAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function execute(User $client, array $data, array $images = []): MedicalRecord
    {
        return DB::transaction(function () use ($client, $data, $images) {
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
                'doctor_id',
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

            $payload['client_id'] = $client->id;

            if ($companionId !== null) {
                $payload['companion_id'] = $companionId;
            }

            $record = MedicalRecord::query()->updateOrCreate(
                ['client_id' => $client->id],
                $payload,
            );

            foreach ($images as $image) {
                $path = $image->store('medical_records/'.$client->id, 'public');
                RecordImage::query()->create([
                    'medical_record_id' => $record->id,
                    'file_path' => $path,
                ]);
            }

            return $record->load(['client', 'companion', 'doctor', 'supervisor', 'admin', 'images']);
        });
    }
}
