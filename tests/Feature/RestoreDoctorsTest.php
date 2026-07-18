<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Models\Category;
use App\Models\DoctorProfile;
use App\Models\Post;
use App\Models\Resume;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestoreDoctorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_restore_doctors_from_legacy_json_by_phone(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $hashedPassword = Hash::make('secret-pass');

        $response = $this->postJson('/api/v1/restore/doctors', [
            'data' => [
                [
                    'id' => 1,
                    'name' => 'دکتر علی محرابی',
                    'avatar' => 'doctor_avatars/avatar.jpg',
                    'times' => null,
                    'days' => 'null',
                    'resume' => 'doctor_resumes/resume.pdf',
                    'phone' => '09131889355',
                    'national_code' => '5110245123',
                    'birth_date' => '1981-03-16',
                    'card_number' => '6037697144523652',
                    'medical_number' => '51223654',
                    'email' => 'ali@gmail.com',
                    'password' => $hashedPassword,
                    'profile_path' => null,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Data restored successfully.');

        $doctor = User::query()->where('phone', '09131889355')->first();

        $this->assertNotNull($doctor);
        $this->assertSame(UserType::Doctor, $doctor->type);
        $this->assertSame('دکتر علی محرابی', $doctor->name);
        $this->assertTrue(Hash::check('secret-pass', $doctor->password));

        $profile = DoctorProfile::query()->where('user_id', $doctor->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('5110245123', $profile->national_code);
        $this->assertSame('doctor_avatars/avatar.jpg', $profile->avatar);
        $this->assertNull($profile->days);

        $resume = Resume::query()->where('doctor_id', $doctor->id)->first();
        $this->assertNotNull($resume);
        $this->assertSame('doctor_resumes/resume.pdf', $resume->file_path);
    }

    public function test_doctor_restore_preserves_existing_admin_boss_account(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create([
            'phone' => '09131889355',
            'name' => 'Boss Seed',
            'password' => 'boss-pass',
        ]);
        Sanctum::actingAs($admin);

        $hashedPassword = Hash::make('doctor-pass');

        $response = $this->postJson('/api/v1/restore/doctors', [
            'data' => [
                [
                    'name' => 'دکتر علی محرابی',
                    'phone' => '09131889355',
                    'national_code' => '5110245123',
                    'email' => 'ali@gmail.com',
                    'password' => $hashedPassword,
                ],
            ],
        ]);

        $response->assertOk();

        $user = User::query()->where('phone', '09131889355')->first();

        $this->assertNotNull($user);
        $this->assertSame(1, User::query()->where('phone', '09131889355')->count());
        $this->assertSame(UserType::Admin, $user->type);
        $this->assertSame(AdminRole::Boss, $user->admin_role);
        $this->assertSame('دکتر علی محرابی', $user->name);
        $this->assertTrue($user->isActingAsDoctor());
        $this->assertTrue(Hash::check('boss-pass', $user->password));
        $this->assertDatabaseHas('doctor_profiles', [
            'user_id' => $user->id,
            'national_code' => '5110245123',
        ]);
    }

    public function test_doctor_restore_is_idempotent_by_phone(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $payload = [
            'data' => [
                [
                    'name' => 'دکتر تست',
                    'phone' => '09120000001',
                    'national_code' => '1234567890',
                    'email' => 'doc@example.com',
                ],
            ],
        ];

        $this->postJson('/api/v1/restore/doctors', $payload)->assertOk();
        $this->postJson('/api/v1/restore/doctors', [
            'data' => [
                [
                    'name' => 'دکتر تست به‌روز',
                    'phone' => '09120000001',
                    'national_code' => '1234567890',
                    'email' => 'doc@example.com',
                ],
            ],
        ])->assertOk();

        $this->assertSame(1, User::query()->where('type', UserType::Doctor)->count());
        $this->assertSame('دکتر تست به‌روز', User::query()->where('phone', '09120000001')->value('name'));
    }

    public function test_resume_restore_matches_doctor_by_phone_not_id(): void
    {
        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $doctor = User::factory()->doctor()->create([
            'phone' => '09131889355',
            'name' => 'دکتر علی محرابی',
        ]);
        DoctorProfile::query()->create([
            'user_id' => $doctor->id,
            'national_code' => '5110245123',
        ]);

        $response = $this->postJson('/api/v1/restore/resumes', [
            'data' => [
                [
                    'id' => 99,
                    'doctor_id' => 1,
                    'doctor_phone' => '09131889355',
                    'title' => 'رزومه دکتر علی محرابی',
                    'bio' => 'بیوگرافی',
                    'specialization' => 'رواندرمانگر',
                    'educations' => [],
                    'experiences' => [],
                    'skills' => ['CBT'],
                    'social_links' => ['instagram' => 'https://instagram.com/x'],
                    'file_path' => null,
                ],
            ],
        ]);

        $response->assertOk();

        $resume = Resume::query()->where('doctor_id', $doctor->id)->first();
        $this->assertNotNull($resume);
        $this->assertSame('رزومه دکتر علی محرابی', $resume->title);
        $this->assertSame(['CBT'], $resume->skills);
    }

    public function test_post_restore_resolves_author_and_tags_by_natural_keys(): void
    {
        $admin = User::factory()->admin(AdminRole::Author)->create([
            'phone' => '09140379929',
        ]);
        Sanctum::actingAs($admin);

        Category::query()->create([
            'name' => 'سلامت روان عمومی',
            'slug' => 'general-mental-health',
            'content' => 'x',
        ]);
        Tag::query()->create([
            'name' => 'سلامت روان',
            'slug' => 'mental-health',
        ]);

        $response = $this->postJson('/api/v1/restore/posts', [
            'data' => [
                [
                    'id' => 5,
                    'admin_id' => 3,
                    'category_id' => 3,
                    'author_phone' => '09140379929',
                    'category_slug' => 'general-mental-health',
                    'tag_slugs' => ['mental-health'],
                    'title' => 'عنوان تست',
                    'slug' => 'mental-health-post',
                    'excerpt' => 'خلاصه',
                    'content' => 'محتوا',
                    'status' => 'published',
                ],
            ],
        ]);

        $response->assertOk();

        $post = Post::query()->where('slug', 'mental-health-post')->first();
        $this->assertNotNull($post);
        $this->assertSame($admin->id, $post->author_id);
        $this->assertSame('general-mental-health', $post->category->slug);
        $this->assertTrue($post->tags->contains(fn (Tag $tag) => $tag->slug === 'mental-health'));
    }

    public function test_restore_accepts_uploaded_json_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin(AdminRole::Boss)->create();
        Sanctum::actingAs($admin);

        $json = json_encode([
            [
                'name' => 'دکتر فایل',
                'phone' => '09129998877',
                'national_code' => '9988776655',
            ],
        ], JSON_THROW_ON_ERROR);

        $file = UploadedFile::fake()->createWithContent('doctors.json', $json);

        $response = $this->post('/api/v1/restore/doctors', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'phone' => '09129998877',
            'type' => UserType::Doctor->value,
        ]);
    }
}
