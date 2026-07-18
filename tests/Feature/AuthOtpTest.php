<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\OtpPurpose;
use App\Enums\UserType;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_request_and_verify_login_otp(): void
    {
        User::factory()->admin(AdminRole::Boss)->create([
            'phone' => '09121111111',
            'password' => 'password',
        ]);

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (string $phone, string $message) => $phone === '09121111111' && str_contains($message, 'کد ورود'))
                ->andReturn(true);
        });

        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '09121111111',
            'type' => 'admin',
        ])->assertOk()
            ->assertJsonPath('message', 'کد تأیید ارسال شد.');

        Cache::put(
            'otp:code:'.OtpPurpose::Login->value.':09121111111',
            Hash::make('123456'),
            now()->addMinutes(5),
        );

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '09121111111',
            'type' => 'admin',
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Logged in successfully.')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'phone', 'type'],
                ],
            ])
            ->assertJsonPath('data.user.type', UserType::Admin->value);
    }

    public function test_login_otp_rejects_unknown_phone(): void
    {
        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('send');
        });

        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '09129999999',
            'type' => 'admin',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_login_otp_rejects_invalid_code(): void
    {
        User::factory()->admin()->create([
            'phone' => '09122222222',
        ]);

        Cache::put(
            'otp:code:'.OtpPurpose::Login->value.':09122222222',
            Hash::make('123456'),
            now()->addMinutes(5),
        );

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '09122222222',
            'type' => 'admin',
            'code' => '000000',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_authenticated_admin_can_change_password_with_otp(): void
    {
        $user = User::factory()->admin(AdminRole::Boss)->create([
            'phone' => '09123333333',
            'password' => 'old-password',
        ]);

        Sanctum::actingAs($user);

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->once()->andReturn(true);
        });

        $this->postJson('/api/v1/auth/password/otp')
            ->assertOk()
            ->assertJsonPath('message', 'کد تأیید تغییر رمز ارسال شد.');

        Cache::put(
            'otp:code:'.OtpPurpose::PasswordChange->value.':09123333333',
            Hash::make('654321'),
            now()->addMinutes(5),
        );

        $this->postJson('/api/v1/auth/password', [
            'code' => '654321',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()
            ->assertJsonPath('message', 'رمز عبور با موفقیت تغییر کرد.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_password_change_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/password/otp')->assertUnauthorized();
        $this->postJson('/api/v1/auth/password', [
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnauthorized();
    }

    public function test_doctor_can_request_login_otp(): void
    {
        User::factory()->doctor()->create([
            'phone' => '09124444444',
        ]);

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->once()->andReturn(true);
        });

        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '09124444444',
            'type' => 'doctor',
        ])->assertOk();
    }
}
