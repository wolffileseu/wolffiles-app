<?php

namespace App\Models\BugTracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Watcher extends Model
{
    use HasFactory;

    protected $table = 'bt_watchers';

    protected $fillable = [
        'task_id', 'user_id',
        'notify_status', 'notify_comments', 'notify_assignment',
    ];

    protected $casts = [
        'notify_status'     => 'boolean',
        'notify_comments'   => 'boolean',
        'notify_assignment' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
