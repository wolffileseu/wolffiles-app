<?php

namespace App\Models\BugTracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $table = 'bt_projects';

    protected $fillable = [
        'slug', 'name', 'description', 'color', 'icon',
        'github_repo', 'github_sync_enabled',
        'default_assignee_id', 'is_public', 'is_active',
        'sort_order', 'discord_webhook_url', 'telegram_chat_id',
    ];

    protected $casts = [
        'github_sync_enabled' => 'boolean',
        'is_public'           => 'boolean',
        'is_active'           => 'boolean',
        'sort_order'          => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
