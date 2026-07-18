<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendMultiSmsRequest;
use App\Http\Requests\Sms\SendSingleSmsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;

class SmsController extends Controller
{
    public function single(SendSingleSmsRequest $request, SmsService $sms): JsonResponse
    {
        $sent = $sms->send(
            $request->validated('phone'),
            $request->validated('message'),
        );

        if (! $sent) {
            return ApiResponse::error('SMS could not be sent.', 502);
        }

        return ApiResponse::success(null, 'SMS sent successfully.');
    }

    public function multi(SendMultiSmsRequest $request, SmsService $sms): JsonResponse
    {
        $sent = $sms->send(
            $request->validated('phones'),
            $request->validated('message'),
        );

        if (! $sent) {
            return ApiResponse::error('SMS could not be sent.', 502);
        }

        return ApiResponse::success(null, 'SMS messages sent successfully.');
    }
}
