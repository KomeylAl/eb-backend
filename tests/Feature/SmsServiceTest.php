<?php

namespace Tests\Feature;

use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    public function test_send_posts_expected_payload_to_sms_ir(): void
    {
        config([
            'services.sms_ir.api_key' => 'test-api-key',
            'services.sms_ir.line_number' => '9982005424',
        ]);

        Http::fake([
            'https://api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 1,
                'message' => 'موفق',
                'data' => [
                    'packId' => '2b99e63c-9bf8-4a21-9bfe-3f72dc1b46f1',
                    'messageIds' => [86522023],
                    'cost' => 1.0,
                ],
            ], 200),
        ]);

        $sent = app(SmsService::class)->send('09121111111', 'پیام تست');

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.sms.ir/v1/send/bulk'
                && $request->hasHeader('X-API-KEY', 'test-api-key')
                && $request['lineNumber'] === 9982005424
                && $request['messageText'] === 'پیام تست'
                && $request['mobiles'] === ['09121111111'];
        });
    }

    public function test_send_fails_when_provider_status_is_not_success(): void
    {
        config([
            'services.sms_ir.api_key' => 'test-api-key',
            'services.sms_ir.line_number' => '9982005424',
        ]);

        Http::fake([
            'https://api.sms.ir/v1/send/bulk' => Http::response([
                'status' => 0,
                'message' => 'خطا',
            ], 200),
        ]);

        $sent = app(SmsService::class)->send('09121111111', 'پیام تست');

        $this->assertFalse($sent);
    }

    public function test_send_fails_when_credentials_missing(): void
    {
        config([
            'services.sms_ir.api_key' => null,
            'services.sms_ir.line_number' => null,
        ]);

        Http::fake();

        $sent = app(SmsService::class)->send('09121111111', 'پیام تست');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }
}
