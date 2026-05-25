<?php

namespace App\Models\BugTracker;

use App\Enums\BugTracker\TaskPriority;
use App\Enums\BugTracker\TaskSeverity;
use App\Enums\BugTracker\TaskStatus;
use App\Enums\BugTracker\TaskType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $table = 'bt_tasks';

    protected $fillable = [
        'project_id', 'category_id', 'task_number',
        'title', 'description',
        'status', 'priority', 'severity', 'type',
        'reporter_id', 'reporter_name', 'reporter_email', 'assignee_id',
        'affected_version', 'target_version',
        'resolved_at', 'closed_at', 'due_date',
        'github_issue_number', 'github_issue_url', 'github_last_synced_at',
        'views_count', 'last_activity_at',
    ];

    protected $casts = [
        'status'                => TaskStatus::class,
        'priority'              => TaskPriority::class,
        'severity'              => TaskSeverity::class,
        'type'                  => TaskType::class,
        'resolved_at'           => 'datetime',
        'closed_at'             => 'datetime',
        'due_date'              => 'date',
        'github_last_synced_at' => 'datetime',
        'last_activity_at'      => 'datetime',
        'task_number'           => 'integer',
        'views_count'           => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(Watcher::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderByDesc('created_at');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'bt_task_tag', 'task_id', 'tag_id');
    }

    public function getDisplayIdAttribute(): string
    {
        return ($this->project?->slug ? strtoupper($this->project->slug) : 'BT').'-'.$this->task_number;
    }
}
