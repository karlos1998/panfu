<?php

namespace App\Domain\Blog\Services;

use App\Models\BlogPost;
use App\Models\User;

class BlogCommentService
{
    public function create(BlogPost $post, User $user, string $body): void
    {
        abort_unless($post->published_at && $post->published_at->isPast(), 404);

        $post->comments()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'body' => $body,
            'approved_at' => now(),
        ]);
    }
}
