<?php

namespace App\Actions\Backup;

use App\Enums\UserType;
use App\Models\About;
use App\Models\Backup;
use App\Models\Category;
use App\Models\Post;
use App\Models\Resume;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ExportBackupAction
{
    /**
     * @return array{backup: Backup, url: string}
     */
    public function execute(string $type): array
    {
        $payload = $this->resolveCollection($type)
            ->map(fn (Model $model) => $this->serialize($type, $model))
            ->values()
            ->all();

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $fileName = 'backups/'.$type.'/'.$type.'_backup_'.now()->format('Y-m-d_H-i-s').'.json';

        Storage::disk('public')->put($fileName, $json);
        $url = Storage::disk('public')->url($fileName);

        $backup = Backup::query()->create([
            'type' => $type,
            'file_path' => $fileName,
            'file_url' => $url,
        ]);

        return [
            'backup' => $backup,
            'url' => $url,
        ];
    }

    /**
     * @return Collection<int, Model>
     */
    private function resolveCollection(string $type): Collection
    {
        return match ($type) {
            'admins' => User::query()->where('type', UserType::Admin)->get(),
            'doctors' => User::query()->actingAsDoctors()->with(['doctorProfile', 'resume'])->get(),
            'clients' => User::query()->where('type', UserType::Client)->get(),
            'resumes' => Resume::query()->with('doctor')->get(),
            'posts' => Post::query()->with(['author', 'category', 'tags'])->get(),
            'categories' => Category::query()->get(),
            'tags' => Tag::query()->get(),
            'workshops' => Workshop::query()->get(),
            'about' => About::query()->get(),
            default => throw new InvalidArgumentException("Unsupported backup type [{$type}]."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(string $type, Model $model): array
    {
        $data = $model->toArray();
        unset($data['password'], $data['remember_token']);

        return match ($type) {
            'doctors' => $this->serializeDoctor($model, $data),
            'resumes' => $this->serializeResume($model, $data),
            'posts' => $this->serializePost($model, $data),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function serializeDoctor(Model $model, array $data): array
    {
        /** @var User $model */
        $profile = $model->doctorProfile;

        return [
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
            'email' => $model->email,
            'birth_date' => $model->birth_date?->toDateString(),
            'national_code' => $profile?->national_code,
            'card_number' => $profile?->card_number,
            'medical_number' => $profile?->medical_number,
            'avatar' => $profile?->avatar,
            'days' => $profile?->days,
            'times' => $profile?->times,
            'profile_path' => $profile?->profile_path,
            'sort_order' => $profile?->sort_order ?? 0,
            'resume' => $model->resume ? [
                'title' => $model->resume->title,
                'bio' => $model->resume->bio,
                'specialization' => $model->resume->specialization,
                'educations' => $model->resume->educations,
                'experiences' => $model->resume->experiences,
                'skills' => $model->resume->skills,
                'certifications' => $model->resume->certifications,
                'social_links' => $model->resume->social_links,
                'content' => $model->resume->content,
                'file_path' => $model->resume->file_path,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function serializeResume(Model $model, array $data): array
    {
        /** @var Resume $model */
        unset($data['doctor']);

        return array_merge($data, [
            'doctor_phone' => $model->doctor?->phone,
            'doctor_id' => $model->doctor_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function serializePost(Model $model, array $data): array
    {
        /** @var Post $model */
        unset($data['author'], $data['category'], $data['tags']);

        return array_merge($data, [
            'author_phone' => $model->author?->phone,
            'category_slug' => $model->category?->slug,
            'tag_slugs' => $model->tags->pluck('slug')->values()->all(),
        ]);
    }
}
