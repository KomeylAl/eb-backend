<?php

namespace App\Enums;

use App\Models\Post;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Model;
use ValueError;

enum CommentableType: string
{
    case Post = 'post';
    case Doctor = 'doctor';
    case Workshop = 'workshop';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Post => Post::class,
            self::Doctor => User::class,
            self::Workshop => Workshop::class,
        };
    }

    public static function tryFromModelClass(?string $modelClass): ?self
    {
        if ($modelClass === null) {
            return null;
        }

        return match ($modelClass) {
            Post::class => self::Post,
            User::class => self::Doctor,
            Workshop::class => self::Workshop,
            default => null,
        };
    }

    public static function fromModelClass(string $modelClass): self
    {
        return self::tryFromModelClass($modelClass)
            ?? throw new ValueError("Unsupported commentable model [{$modelClass}].");
    }
}
