<?php

namespace App\Models\BugTracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'bt_comments';

    protected $fillable = [
        'task_id', 'user_id', 'author_name', 'body',
        'is_internal', 'github_comment_id', 'github_last_synced_at', 'edited_at',
    ];

    protected $casts = [
        'is_internal'           => 'boolean',
        'github_last_synced_at' => 'datetime',
        'edited_at'             => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
