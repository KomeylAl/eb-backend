<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private SmsService $sms) {}

    /**
     * Generate, store, and SMS an OTP code.
     *
     * @throws ValidationException
     */
    public function send(string $phone, OtpPurpose $purpose): void
    {
        $this->ensureNotThrottled($phone, $purpose);

        $code = (string) random_int(100000, 999999);
        $ttlSeconds = (int) config('services.sms_ir.otp_ttl_seconds', 300);

        Cache::put(
            $this->codeKey($phone, $purpose),
            Hash::make($code),
            now()->addSeconds($ttlSeconds),
        );

        Cache::put(
            $this->throttleKey($phone, $purpose),
            true,
            now()->addSeconds((int) config('services.sms_ir.otp_resend_seconds', 60)),
        );

        $message = match ($purpose) {
            OtpPurpose::Login => "کد ورود ابراز: {$code}\nاین کد تا ".((int) ($ttlSeconds / 60)).' دقیقه معتبر است.',
            OtpPurpose::PasswordChange => "کد تأیید تغییر رمز ابراز: {$code}\nاین کد تا ".((int) ($ttlSeconds / 60)).' دقیقه معتبر است.',
        };

        if (! $this->sms->send($phone, $message)) {
            Cache::forget($this->codeKey($phone, $purpose));
            Cache::forget($this->throttleKey($phone, $purpose));

            throw ValidationException::withMessages([
                'phone' => ['ارسال پیامک ناموفق بود. لطفاً دوباره تلاش کنید.'],
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $phone, OtpPurpose $purpose, string $code, bool $forgetOnSuccess = true): void
    {
        $hashed = Cache::get($this->codeKey($phone, $purpose));

        if (! is_string($hashed) || $hashed === '' || ! Hash::check($code, $hashed)) {
            throw ValidationException::withMessages([
                'code' => ['کد تأیید نامعتبر یا منقضی شده است.'],
            ]);
        }

        if ($forgetOnSuccess) {
            Cache::forget($this->codeKey($phone, $purpose));
            Cache::forget($this->throttleKey($phone, $purpose));
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureNotThrottled(string $phone, OtpPurpose $purpose): void
    {
        if (Cache::has($this->throttleKey($phone, $purpose))) {
            throw ValidationException::withMessages([
                'phone' => ['لطفاً کمی صبر کنید و دوباره درخواست کد دهید.'],
            ]);
        }
    }

    private function codeKey(string $phone, OtpPurpose $purpose): string
    {
        return "otp:code:{$purpose->value}:{$phone}";
    }

    private function throttleKey(string $phone, OtpPurpose $purpose): string
    {
        return "otp:throttle:{$purpose->value}:{$phone}";
    }
}
