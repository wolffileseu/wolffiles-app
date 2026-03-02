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

    public function selectedDirectories(): BelongsToMany
    {
        return $this->belongsToMany(FastDlDirectory::class, 'fastdl_clan_directories', 'clan_id', 'directory_id');
    }

    public function ownFiles(): HasMany
    {
        return $this->hasMany(FastDlClanFile::class, 'clan_id');
    }
}
