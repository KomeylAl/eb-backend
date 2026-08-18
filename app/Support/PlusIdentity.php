<?php

namespace App\Support;

use App\Enums\UserType;
use App\Http\Resources\ClientResource;
use App\Http\Resources\PlusParticipantResource;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class PlusIdentity
{
    public function __construct(
        public readonly Authenticatable $actor,
        public readonly ?User $client,
        public readonly ?Participant $participant,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');

        if ($user instanceof Participant) {
            $client = User::query()
                ->where('type', UserType::Client)
                ->where('phone', $user->phone)
                ->first();

            return new self($user, $client, $user);
        }

        if ($user instanceof User && $user->isClient()) {
            $participant = Participant::query()
                ->where('phone', $user->phone)
                ->first();

            return new self($user, $user, $participant);
        }

        abort(403, 'Forbidden.');
    }

    public function name(): string
    {
        return $this->client?->name ?? $this->participant?->name ?? '';
    }

    public function phone(): string
    {
        return $this->client?->phone ?? $this->participant?->phone ?? '';
    }

    public function requireClient(): User
    {
        abort_unless($this->client, 403, 'این بخش فقط برای مراجعان در دسترس است.');

        return $this->client;
    }

    public function requireParticipant(): Participant
    {
        abort_unless($this->participant, 403, 'این بخش فقط برای شرکت‌کنندگان در دسترس است.');

        return $this->participant;
    }

    public function toProfileArray(): array
    {
        return [
            'id' => $this->client?->id ?? $this->participant?->id,
            'name' => $this->name(),
            'phone' => $this->phone(),
            'birth_date' => $this->client?->birth_date?->toDateString(),
            'address' => $this->client?->address,
            'english_name' => $this->participant?->english_name,
            'gender' => $this->participant?->gender?->value,
            'is_client' => $this->client !== null,
            'is_participant' => $this->participant !== null,
            'has_password' => filled($this->client?->getAuthPassword()),
            'client' => $this->client ? ClientResource::make($this->client)->resolve() : null,
            'participant' => $this->participant
                ? PlusParticipantResource::make($this->participant)->resolve()
                : null,
        ];
    }
}
