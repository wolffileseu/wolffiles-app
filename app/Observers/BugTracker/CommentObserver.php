<?php

namespace App\Observers\BugTracker;

use App\Models\BugTracker\Comment;

class CommentObserver
{
    public function creating(Comment $comment): void
    {
        if (empty($comment->user_id) && auth()->check()) {
            $comment->user_id = auth()->id();
        }
    }

    public function created(Comment $comment): void
    {
        $comment->task?->update(['last_activity_at' => now()]);
    }

    public function updating(Comment $comment): void
    {
        if ($comment->isDirty('body')) {
            $comment->edited_at = now();
        }
    }
}
