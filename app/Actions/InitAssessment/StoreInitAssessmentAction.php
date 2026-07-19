<?php

namespace App\Actions\InitAssessment;

use App\Enums\UserType;
use App\Models\InitAssessment;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use IntlDateFormatter;

class StoreInitAssessmentAction
{
    public function __construct(private SmsService $sms) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): InitAssessment
    {
        $assessment = DB::transaction(function () use ($data) {
            $clientData = $data['client'];

            $client = User::query()
                ->where('phone', $clientData['phone'])
                ->first();

            if (! $client) {
                $client = User::query()->create([
                    'name' => $clientData['name'],
                    'phone' => $clientData['phone'],
                    'birth_date' => $clientData['birth_date'] ?? null,
                    'address' => $clientData['address'] ?? null,
                    'type' => UserType::Client,
                ]);
            } elseif ($client->type === UserType::Client) {
                $client->update([
                    'name' => $clientData['name'],
                    'birth_date' => $clientData['birth_date'] ?? $client->birth_date,
                    'address' => $clientData['address'] ?? $client->address,
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

        $this->notifyBySms(
            $data['client']['name'],
            $data['client']['phone'],
            $assessment,
        );

        return $assessment;
    }

    /**
     * SMS failures are logged inside SmsService and must not fail the registration.
     */
    private function notifyBySms(string $clientName, string $clientPhone, InitAssessment $assessment): void
    {
        $this->sms->send(
            $clientPhone,
            "کلینیک ابراز\n{$clientName} عزیز، درخواست ارزیابی شما با موفقیت ثبت شد. همکاران ما به زودی با شما تماس خواهند گرفت.",
        );

        $centerPhone = config('services.clinic.center_phone');

        if (blank($centerPhone)) {
            return;
        }

        $registeredAt = $this->formatPersianDateTime($assessment->created_at ?? now());

        $this->sms->send(
            $centerPhone,
            "کلینیک ابراز\n{$clientName} با شماره تلفن {$clientPhone} در تاریخ {$registeredAt} درخواست ارزیابی اولیه ثبت کرده است.",
        );
    }

    private function formatPersianDateTime(\DateTimeInterface $dateTime): string
    {
        $formatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT,
            'Asia/Tehran',
            IntlDateFormatter::TRADITIONAL,
        );

        return (string) $formatter->format($dateTime);
    }
}
