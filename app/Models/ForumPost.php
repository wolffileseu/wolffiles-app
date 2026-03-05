<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'forum_thread_id', 'user_id', 'body',
        'is_solution', 'edited_at', 'edited_by',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function getIsFirstPostAttribute(): bool
    {
        return $this->id === $this->thread->posts()->min('id');
    }

    // Kann dieser User den Post bearbeiten?
    public function canEdit($user): bool
    {
        if (!$user) return false;
        if ($user->hasRole(['admin', 'moderator'])) return true;
        return $user->id === $this->user_id;
    }

    // Kann dieser User den Post löschen?
    public function canDelete($user): bool
    {
        if (!$user) return false;
        if ($user->hasRole(['admin', 'moderator'])) return true;
        return $user->id === $this->user_id;
    }

    // Kann dieser User den Thread moderieren (pinnen/sperren)?
    public static function canModerate($user): bool
    {
        if (!$user) return false;
        return $user->hasRole(['admin', 'moderator']);
    }

    protected static function booted(): void
    {
        static::created(function (ForumPost $post) {
            $post->thread->refreshPostsCount();
            $post->thread->refreshLastPost();
        });

        static::deleted(function (ForumPost $post) {
            $post->thread->refreshPostsCount();
            $post->thread->refreshLastPost();
        });
    }
}
