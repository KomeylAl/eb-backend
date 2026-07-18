<?php

namespace App\Actions\Workshop;

use App\Models\Participant;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterWorkshopParticipantAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Workshop $workshop, array $data): Participant
    {
        return DB::transaction(function () use ($workshop, $data) {
            $participant = null;

            if (! empty($data['national_code'])) {
                $participant = Participant::query()
                    ->where('national_code', $data['national_code'])
                    ->first();
            }

            if (! $participant && ! empty($data['phone'])) {
                $participant = Participant::query()
                    ->where('phone', $data['phone'])
                    ->first();
            }

            $payload = [
                'name' => $data['name'],
                'english_name' => $data['english_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'national_code' => $data['national_code'] ?? null,
                'gender' => $data['gender'] ?? null,
            ];

            if ($participant) {
                $participant->update($payload);
            } else {
                $participant = Participant::query()->create($payload);
            }

            $alreadyRegistered = $workshop->participants()
                ->where('participants.id', $participant->id)
                ->exists();

            if ($alreadyRegistered) {
                $workshop->participants()->updateExistingPivot($participant->id, [
                    'approved' => (bool) ($data['approved'] ?? false),
                ]);
            } else {
                $workshop->participants()->attach($participant->id, [
                    'id' => (string) Str::uuid(),
                    'registered_at' => now(),
                    'approved' => (bool) ($data['approved'] ?? false),
                ]);
            }

            return $participant->load(['workshops' => fn ($q) => $q->where('workshops.id', $workshop->id)]);
        });
    }
}
