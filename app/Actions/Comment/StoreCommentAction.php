<?php

namespace App\Actions\Comment;

use App\Enums\CommentableType;
use App\Enums\UserType;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class StoreCommentAction
{
    /**
     * @param  array{
     *     commentable_type: string,
     *     commentable_id: string,
     *     first_name: string,
     *     last_name: string,
     *     phone: string,
     *     body: string,
     *     rating: int
     * }  $data
     */
    public function execute(array $data, ?User $actor = null): Comment
    {
        $commentableType = CommentableType::from($data['commentable_type']);
        $commentable = $this->resolveCommentable($commentableType, $data['commentable_id']);

        $comment = Comment::query()->create([
            'commentable_type' => $commentable::class,
            'commentable_id' => $commentable->getKey(),
            'user_id' => $this->resolveUserId($data['phone'], $actor),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'body' => $data['body'],
            'rating' => $data['rating'],
            'approved' => false,
        ]);

        return $comment->load('user');
    }

    private function resolveCommentable(CommentableType $type, string $id): Model
    {
        $modelClass = $type->modelClass();

        /** @var Model|null $model */
        $model = $modelClass::query()->find($id);

        if ($model === null) {
            throw (new ModelNotFoundException)->setModel($modelClass, [$id]);
        }

        if ($type === CommentableType::Doctor) {
            /** @var User $model */
            if (! $model->isActingAsDoctor()) {
                throw ValidationException::withMessages([
                    'commentable_id' => ['Selected doctor is invalid.'],
                ]);
            }
        }

        return $model;
    }

    private function resolveUserId(string $phone, ?User $actor): ?string
    {
        if ($actor?->isClient()) {
            return $actor->id;
        }

        return User::query()
            ->where('phone', $phone)
            ->where('type', UserType::Client)
            ->value('id');
    }
}
