<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'sup_categories';

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color',
        'sort_order', 'is_active', 'required_permission',
        'discord_channel_id', 'discord_role_id',
        'default_assignee_id', 'allow_guests',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'allow_guests' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(CategorySubscription::class, 'category_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
