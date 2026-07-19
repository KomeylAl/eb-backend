<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class InitAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.clinic.center_phone' => '09228728245']);

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->andReturn(true)->byDefault();
        });
    }

    public function test_guest_can_register_assessment_with_new_phone(): void
    {
        $response = $this->postJson('/api/v1/assessments', [
            'status' => 'pending',
            'client' => [
                'name' => 'مراجع جدید',
                'phone' => '09120000010',
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'phone' => '09120000010',
            'type' => UserType::Client->value,
        ]);
    }

    public function test_assessment_registration_sends_sms_to_client_and_center(): void
    {
        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (string $phone, string $message) {
                    return $phone === '09120000012'
                        && str_contains($message, 'کلینیک ابراز')
                        && str_contains($message, 'مراجع پیامکی عزیز')
                        && str_contains($message, 'با موفقیت ثبت شد');
                })
                ->andReturn(true);

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (string $phone, string $message) {
                    return $phone === '09228728245'
                        && str_contains($message, 'مراجع پیامکی')
                        && str_contains($message, '09120000012')
                        && str_contains($message, 'درخواست ارزیابی اولیه ثبت کرده است');
                })
                ->andReturn(true);
        });

        $this->postJson('/api/v1/assessments', [
            'status' => 'pending',
            'client' => [
                'name' => 'مراجع پیامکی',
                'phone' => '09120000012',
            ],
        ])->assertCreated();
    }

    public function test_assessment_registration_succeeds_even_if_sms_fails(): void
    {
        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->andReturn(false);
        });

        $this->postJson('/api/v1/assessments', [
            'status' => 'pending',
            'client' => [
                'name' => 'مراجع بدون پیامک',
                'phone' => '09120000013',
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'phone' => '09120000013',
        ]);
    }

    public function test_assessment_with_existing_client_phone_reuses_user(): void
    {
        $client = User::factory()->client()->create([
            'phone' => '09120000011',
            'name' => 'نام قدیمی',
        ]);

        $this->postJson('/api/v1/assessments', [
            'status' => 'pending',
            'client' => [
                'name' => 'نام جدید',
                'phone' => '09120000011',
            ],
        ])->assertCreated();

        $this->assertSame(1, User::query()->where('phone', '09120000011')->count());
        $this->assertSame('نام جدید', $client->fresh()->name);
    }

    public function test_assessment_with_admin_phone_does_not_duplicate_or_alter_admin(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create([
            'phone' => '09140379929',
            'name' => 'Admin User',
        ]);

        $this->postJson('/api/v1/assessments', [
            'status' => 'pending',
            'client' => [
                'name' => 'کمیل ابدال',
                'phone' => '09140379929',
            ],
        ])->assertCreated();

        $this->assertSame(1, User::query()->where('phone', '09140379929')->count());

        $admin->refresh();
        $this->assertSame(UserType::Admin, $admin->type);
        $this->assertSame(AdminRole::Boss, $admin->admin_role);
        $this->assertSame('Admin User', $admin->name);

        $this->assertDatabaseHas('assessment_user', [
            'client_id' => $admin->id,
        ]);
    }

    public function test_assessment_with_doctor_phone_does_not_duplicate_doctor(): void
    {
        $doctor = User::factory()->doctor()->create([
            'phone' => '09131889355',
        ]);

        $this->postJson('/api/v1/assessments', [
            'status' => 'pending',
            'client' => [
                'name' => 'دکتر به عنوان مراجع',
                'phone' => '09131889355',
            ],
        ])->assertCreated();

        $this->assertSame(1, User::query()->where('phone', '09131889355')->count());
        $this->assertSame(UserType::Doctor, $doctor->fresh()->type);
    }
}
