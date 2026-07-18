<?php

namespace App\Actions\InitAssessment;

use App\Enums\UserType;
use App\Models\InitAssessment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreInitAssessmentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): InitAssessment
    {
        return DB::transaction(function () use ($data) {
            $clientData = $data['client'];

            $client = User::query()
                ->where('phone', $clientData['phone'])
                ->where('type', UserType::Client)
                ->first();

            if (! $client) {
                $client = User::query()->create([
                    'name' => $clientData['name'],
                    'phone' => $clientData['phone'],
                    'birth_date' => $clientData['birth_date'] ?? null,
                    'address' => $clientData['address'] ?? null,
                    'type' => UserType::Client,
                ]);
            }

            $assessment = InitAssessment::query()->create([
                'date' => $data['date'] ?? null,
                'time' => $data['time'] ?? null,
                'status' => $data['status'],
                'file_path' => $data['file_path'] ?? null,
            ]);

            $assessment->clients()->attach($client->id, [
                'id' => (string) Str::uuid(),
                'doctor_id' => $data['doctor_id'] ?? null,
            ]);

            return $assessment->load(['clients', 'doctors']);
        });
    }
}
