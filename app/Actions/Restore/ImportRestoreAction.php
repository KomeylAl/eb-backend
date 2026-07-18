<?php

namespace App\Actions\Restore;

use App\Enums\AdminRole;
use App\Enums\PostStatus;
use App\Enums\UserType;
use App\Models\About;
use App\Models\Category;
use App\Models\DoctorProfile;
use App\Models\Post;
use App\Models\Restore;
use App\Models\Resume;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImportRestoreAction
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(string $type, array $items): Restore
    {
        return DB::transaction(function () use ($type, $items) {
            $restored = 0;

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $item = $this->normalizeItem($type, $item);

                if ($this->upsert($type, $item)) {
                    $restored++;
                }
            }

            if ($restored === 0) {
                throw new InvalidArgumentException('No valid records were found to restore.');
            }

            return Restore::query()->create(['type' => $type]);
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(string $type, array $item): array
    {
        unset(
            $item['created_at'],
            $item['updated_at'],
            $item['remember_token'],
            $item['email_verified_at'],
            $item['departments'],
            $item['sessions'],
            $item['participants'],
            $item['comments'],
            $item['posts'],
            $item['doctor_profile'],
            $item['doctorProfile'],
        );

        foreach (['logo', 'logo_path', 'thumbnail', 'img_path', 'image', 'avatar', 'file_path'] as $pathField) {
            if (! empty($item[$pathField]) && is_string($item[$pathField])) {
                $item[$pathField] = $this->normalizeStoragePath($item[$pathField]);
            }
        }

        if (array_key_exists('logo_path', $item) && empty($item['logo'])) {
            $item['logo'] = $item['logo_path'];
        }
        unset($item['logo_path']);

        if (array_key_exists('lat', $item) && empty($item['latitude'])) {
            $item['latitude'] = $item['lat'];
        }
        if (array_key_exists('long', $item) && empty($item['longitude'])) {
            $item['longitude'] = $item['long'];
        }
        unset($item['lat'], $item['long']);

        if (array_key_exists('role', $item) && empty($item['admin_role'])) {
            $item['admin_role'] = $item['role'];
        }
        unset($item['role']);

        if ($type === 'posts' && array_key_exists('admin_id', $item) && empty($item['author_id'])) {
            $item['author_id'] = $item['admin_id'];
        }

        if (isset($item['days'])) {
            $item['days'] = $this->normalizeJsonish($item['days']);
        }
        if (isset($item['times'])) {
            $item['times'] = $this->normalizeJsonish($item['times']);
        }

        foreach (['educations', 'experiences', 'skills', 'certifications', 'social_links'] as $jsonField) {
            if (array_key_exists($jsonField, $item)) {
                $item[$jsonField] = $this->normalizeJsonish($item[$jsonField]) ?? [];
            }
        }

        if (array_key_exists('email', $item) && $item['email'] === '') {
            $item['email'] = null;
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsert(string $type, array $item): bool
    {
        return match ($type) {
            'admins' => $this->upsertAdmin($item),
            'doctors' => $this->upsertDoctor($item),
            'clients' => $this->upsertClient($item),
            'resumes' => $this->upsertResume($item),
            'posts' => $this->upsertPost($item),
            'categories' => $this->upsertBySlug(Category::class, $item, ['name', 'slug', 'excerpt', 'content', 'image']),
            'tags' => $this->upsertBySlug(Tag::class, $item, ['name', 'slug', 'excerpt', 'content', 'image']),
            'workshops' => $this->upsertBySlug(Workshop::class, $item, [
                'title', 'slug', 'excerpt', 'content', 'organizers', 'start_date', 'end_date', 'week_day', 'time', 'img_path',
            ]),
            'about' => $this->upsertAbout($item),
            default => throw new InvalidArgumentException("Unsupported restore type [{$type}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertAdmin(array $item): bool
    {
        $phone = $this->requiredString($item, 'phone', 'Admin phone is required for restore.');

        $attributes = [
            'name' => $item['name'] ?? $phone,
            'phone' => $phone,
            'birth_date' => $item['birth_date'] ?? null,
            'type' => UserType::Admin,
            'admin_role' => $this->resolveAdminRole($item['admin_role'] ?? null),
        ];

        $user = User::query()->updateOrCreate(
            ['phone' => $phone],
            $attributes,
        );

        $this->applyPassword($user, $item['password'] ?? null);

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertDoctor(array $item): bool
    {
        $phone = $this->requiredString($item, 'phone', 'Doctor phone is required for restore.');
        $nationalCode = $this->requiredString($item, 'national_code', 'Doctor national_code is required for restore.');

        $existing = User::query()->where('phone', $phone)->first();
        $preserveAdmin = $existing?->type === UserType::Admin;

        $attributes = [
            'name' => $item['name'] ?? $phone,
            'phone' => $phone,
            'email' => $item['email'] ?? null,
            'birth_date' => $item['birth_date'] ?? null,
        ];

        if ($preserveAdmin) {
            $attributes['type'] = UserType::Admin;
            $attributes['admin_role'] = $existing->admin_role;
        } else {
            $attributes['type'] = UserType::Doctor;
            $attributes['admin_role'] = null;
        }

        $user = User::query()->updateOrCreate(
            ['phone' => $phone],
            $attributes,
        );

        if (! $preserveAdmin) {
            $this->applyPassword($user, $item['password'] ?? null);
        }

        DoctorProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'national_code' => $nationalCode,
                'card_number' => $item['card_number'] ?? null,
                'medical_number' => $item['medical_number'] ?? null,
                'avatar' => $item['avatar'] ?? null,
                'days' => $item['days'] ?? null,
                'times' => $item['times'] ?? null,
                'profile_path' => $item['profile_path'] ?? null,
                'sort_order' => (int) ($item['sort_order'] ?? 0),
            ],
        );

        if (array_key_exists('resume', $item) && $item['resume'] !== null && $item['resume'] !== '') {
            $this->upsertResumeForDoctor($user, $item['resume']);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertClient(array $item): bool
    {
        $phone = $this->requiredString($item, 'phone', 'Client phone is required for restore.');

        $user = User::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'name' => $item['name'] ?? $phone,
                'phone' => $phone,
                'birth_date' => $item['birth_date'] ?? null,
                'address' => $item['address'] ?? null,
                'type' => UserType::Client,
                'admin_role' => null,
            ],
        );

        $this->applyPassword($user, $item['password'] ?? null);

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertResume(array $item): bool
    {
        $doctor = $this->resolveDoctor($item);

        if ($doctor === null) {
            throw new InvalidArgumentException(
                'Resume restore requires doctor_phone (or phone / doctor.phone) to match the doctor.'
            );
        }

        $this->upsertResumeForDoctor($doctor, $item);

        return true;
    }

    /**
     * @param  array<string, mixed>|string  $resume
     */
    private function upsertResumeForDoctor(User $doctor, array|string $resume): void
    {
        if (is_string($resume)) {
            $payload = [
                'file_path' => $this->normalizeStoragePath($resume),
            ];
        } else {
            $payload = Arr::only($resume, [
                'title',
                'bio',
                'specialization',
                'educations',
                'experiences',
                'skills',
                'certifications',
                'social_links',
                'content',
                'file_path',
            ]);

            if (! empty($payload['file_path']) && is_string($payload['file_path'])) {
                $payload['file_path'] = $this->normalizeStoragePath($payload['file_path']);
            }
        }

        Resume::query()->updateOrCreate(
            ['doctor_id' => $doctor->id],
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertPost(array $item): bool
    {
        $slug = $this->requiredString($item, 'slug', 'Post slug is required for restore.');

        $author = $this->resolveAuthor($item);
        if ($author === null) {
            throw new InvalidArgumentException(
                "Post [{$slug}] restore requires author_phone (or author.phone) to match an admin/author user."
            );
        }

        $categoryId = $this->resolveCategoryId($item);

        $post = Post::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'author_id' => $author->id,
                'category_id' => $categoryId,
                'title' => $item['title'] ?? $slug,
                'slug' => $slug,
                'excerpt' => $item['excerpt'] ?? null,
                'content' => $item['content'] ?? '',
                'thumbnail' => $item['thumbnail'] ?? null,
                'status' => $this->resolvePostStatus($item['status'] ?? null),
                'published_at' => $item['published_at'] ?? null,
            ],
        );

        $tagIds = $this->resolveTagIds($item);
        if ($tagIds !== null) {
            $post->tags()->detach();
            foreach ($tagIds as $tagId) {
                $post->tags()->attach($tagId, ['id' => (string) Str::uuid()]);
            }
        }

        return true;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  array<string, mixed>  $item
     * @param  list<string>  $fields
     */
    private function upsertBySlug(string $modelClass, array $item, array $fields): bool
    {
        $slug = $this->requiredString($item, 'slug', class_basename($modelClass).' slug is required for restore.');

        $payload = Arr::only($item, $fields);
        $payload['slug'] = $slug;

        if (! isset($payload['name']) && isset($item['title'])) {
            $payload['name'] = $item['title'];
        }
        if (! isset($payload['title']) && isset($item['name'])) {
            $payload['title'] = $item['name'];
        }

        $modelClass::query()->updateOrCreate(['slug' => $slug], $payload);

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertAbout(array $item): bool
    {
        $payload = Arr::only($item, [
            'title',
            'about',
            'phones',
            'mobile_phones',
            'address',
            'logo',
            'latitude',
            'longitude',
        ]);

        $existing = About::query()->first();

        if ($existing) {
            $existing->update($payload);
        } else {
            About::query()->create($payload);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveDoctor(array $item): ?User
    {
        $phone = $item['doctor_phone']
            ?? $item['phone']
            ?? data_get($item, 'doctor.phone');

        if (is_string($phone) && $phone !== '') {
            return User::query()
                ->actingAsDoctors()
                ->where('phone', $phone)
                ->first();
        }

        $doctorId = $item['doctor_id'] ?? data_get($item, 'doctor.id');

        if (is_string($doctorId) && Str::isUuid($doctorId)) {
            return User::query()
                ->actingAsDoctors()
                ->whereKey($doctorId)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveAuthor(array $item): ?User
    {
        $phone = $item['author_phone']
            ?? data_get($item, 'author.phone')
            ?? data_get($item, 'admin.phone');

        if (is_string($phone) && $phone !== '') {
            return User::query()
                ->where('type', UserType::Admin)
                ->where('phone', $phone)
                ->first();
        }

        $authorId = $item['author_id'] ?? null;

        if (is_string($authorId) && Str::isUuid($authorId)) {
            return User::query()
                ->where('type', UserType::Admin)
                ->whereKey($authorId)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveCategoryId(array $item): ?string
    {
        $slug = $item['category_slug'] ?? data_get($item, 'category.slug');

        if (is_string($slug) && $slug !== '') {
            return Category::query()->where('slug', $slug)->value('id');
        }

        $categoryId = $item['category_id'] ?? data_get($item, 'category.id');

        if (is_string($categoryId) && Str::isUuid($categoryId)) {
            return Category::query()->whereKey($categoryId)->value('id');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>|null
     */
    private function resolveTagIds(array $item): ?array
    {
        $slugs = $item['tag_slugs'] ?? null;

        if (is_array($slugs)) {
            $slugs = array_values(array_filter(array_map(
                fn ($slug) => is_string($slug) ? trim($slug) : null,
                $slugs,
            )));

            if ($slugs === []) {
                return [];
            }

            return Tag::query()->whereIn('slug', $slugs)->pluck('id')->all();
        }

        if (isset($item['tags']) && is_array($item['tags'])) {
            $resolved = [];

            foreach ($item['tags'] as $tag) {
                if (is_string($tag) && $tag !== '') {
                    $id = Tag::query()->where('slug', $tag)->value('id');
                    if ($id) {
                        $resolved[] = $id;
                    }
                } elseif (is_array($tag)) {
                    $slug = $tag['slug'] ?? null;
                    if (is_string($slug) && $slug !== '') {
                        $id = Tag::query()->where('slug', $slug)->value('id');
                        if ($id) {
                            $resolved[] = $id;
                        }
                    } elseif (isset($tag['id']) && is_string($tag['id']) && Str::isUuid($tag['id'])) {
                        $id = Tag::query()->whereKey($tag['id'])->value('id');
                        if ($id) {
                            $resolved[] = $id;
                        }
                    }
                }
            }

            return array_values(array_unique($resolved));
        }

        return null;
    }

    private function resolveAdminRole(mixed $role): AdminRole
    {
        if ($role instanceof AdminRole) {
            return $role;
        }

        if (is_string($role) && $role !== '') {
            return AdminRole::tryFrom($role) ?? AdminRole::Receptionist;
        }

        return AdminRole::Receptionist;
    }

    private function resolvePostStatus(mixed $status): PostStatus
    {
        if ($status instanceof PostStatus) {
            return $status;
        }

        if (is_string($status) && $status !== '') {
            return PostStatus::tryFrom($status) ?? PostStatus::Draft;
        }

        return PostStatus::Draft;
    }

    private function applyPassword(User $user, mixed $password): void
    {
        if (! is_string($password) || $password === '') {
            return;
        }

        $hashed = Hash::isHashed($password) ? $password : Hash::make($password);

        DB::table('users')->where('id', $user->id)->update([
            'password' => $hashed,
        ]);
    }

    private function normalizeStoragePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $path = ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');
        }

        return preg_replace('#^storage/#', '', $path) ?? $path;
    }

    private function normalizeJsonish(mixed $value): mixed
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function requiredString(array $item, string $key, string $message): string
    {
        $value = $item[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($message);
        }

        return trim($value);
    }
}
