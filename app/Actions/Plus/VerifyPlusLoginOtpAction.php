<?php

namespace App\Actions\Plus;

use App\Enums\OtpPurpose;
use App\Services\OtpService;
use App\Support\PlusIdentity;

class VerifyPlusLoginOtpAction
{
    public function __construct(
        private ResolvePlusAccountAction $accounts,
        private IssuePlusSessionAction $session,
        private OtpService $otp,
    ) {}

    /**
     * @return array{identity: PlusIdentity, token: string}
     */
    public function execute(string $phone, string $code): array
    {
        $accounts = $this->accounts->execute($phone);
        $this->otp->verify(trim($phone), OtpPurpose::Login, $code);

        return $this->session->execute($accounts['client'], $accounts['participant']);
    }
}
