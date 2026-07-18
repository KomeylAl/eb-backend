<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasUuids;

    protected $fillable = [
        'post_id',
        'user_id',
        'body',
        'author_name',
        'email',
        'approved',
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
