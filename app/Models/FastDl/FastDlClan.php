<?php

namespace App\Models\FastDl;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FastDlClan extends Model
{
    protected $table = 'fastdl_clans';

    protected $fillable = [
        'name', 'slug', 'game_id', 'leader_user_id', 'is_active',
        'include_base', 'storage_limit_mb', 'storage_used_mb', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'include_base' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(FastDlGame::class, 'game_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'fastdl_clan_admins', 'clan_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeManagedBy($query, ?int $userId = null)
    {
        $userId = $userId ?: auth()->id();

        return $query->where(function ($q) use ($userId) {
            $q->where('leader_user_id', $userId)
              ->orWhereHas('admins', fn ($a) => $a->where('users.id', $userId));
        });
    }

    public function isManagedBy(?int $userId = null): bool
    {
        $userId = $userId ?: auth()->id();

        return $this->leader_user_id === $userId
            || $this->admins()->where('users.id', $userId)->exists();
    }

    protected static function booted(): void
    {
        static::saved(function (self $clan) {
            if ($clan->leader_user_id) {
                $clan->admins()->syncWithoutDetaching([$clan->leader_user_id]);
            }
        });
    }

    public function selectedDirectories(): BelongsToMany
    {
        return $this->belongsToMany(FastDlDirectory::class, 'fastdl_clan_directories', 'clan_id', 'directory_id');
    }

    public function ownFiles(): HasMany
    {
        return $this->hasMany(FastDlClanFile::class, 'clan_id');
    }
}
