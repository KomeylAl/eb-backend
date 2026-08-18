<?php

namespace App\Actions\Plus;

use App\Enums\OtpPurpose;
use App\Services\OtpService;

class RequestPlusLoginOtpAction
{
    public function __construct(
        private ResolvePlusAccountAction $accounts,
        private OtpService $otp,
    ) {}

    public function execute(string $phone): void
    {
        $this->accounts->execute($phone);
        $this->otp->send(trim($phone), OtpPurpose::Login);
    }
}
