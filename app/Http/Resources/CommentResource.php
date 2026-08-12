<?php

namespace App\Http\Resources;

use App\Enums\CommentableType;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user('sanctum')?->isAdmin() === true;

        return [
            'id' => $this->id,
            'commentable_type' => CommentableType::tryFromModelClass($this->commentable_type)?->value,
            'commentable_id' => $this->commentable_id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'author_name' => $this->author_full_name,
            'phone' => $this->when($isAdmin, $this->phone),
            'body' => $this->body,
            'rating' => $this->rating,
            'approved' => $this->approved,
            'user' => UserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
