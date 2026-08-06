<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * @param  array<int, string>|string  $phones
     */
    public function send(array|string $phones, string $message): bool
    {
        $apiKey = config('services.sms_ir.api_key');
        $lineNumber = config('services.sms_ir.line_number');

        $mobiles = is_array($phones) ? array_values($phones) : [$phones];
        $mobiles = array_values(array_filter($mobiles, fn (mixed $phone) => filled($phone)));

        if ($mobiles === []) {
            Log::warning('SMS skipped: no valid phone numbers provided.');

            return false;
        }

        if (blank($apiKey) || blank($lineNumber)) {
            Log::warning('SMS skipped: SMS_IR_API_KEY or SMS_IR_LINE_NUMBER is missing.', [
                'phones' => $mobiles,
            ]);

            return false;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->asJson()
                ->post('https://api.sms.ir/v1/send/bulk', [
                    'lineNumber' => (int) $lineNumber,
                    'messageText' => $message,
                    'mobiles' => $mobiles,
                ]);

            $payload = $response->json();
            $providerStatus = data_get($payload, 'status');

            if (! $response->successful() || (int) $providerStatus !== 1) {
                Log::error('SMS.ir send failed.', [
                    'http_status' => $response->status(),
                    'provider_status' => $providerStatus,
                    'body' => $response->body(),
                    'phones' => $mobiles,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS.ir send exception: '.$e->getMessage(), [
                'phones' => $mobiles,
            ]);

            return false;
        }
    }
}
