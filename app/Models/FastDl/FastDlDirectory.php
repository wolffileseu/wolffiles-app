<?php

namespace App\Models\FastDl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $game_id
 * @property string $name
 * @property string $slug
 * @property bool $is_base
 * @property bool $is_active
 */
class FastDlDirectory extends Model
{
    protected $table = 'fastdl_directories';

    protected $fillable = [
        'game_id', 'name', 'slug', 'is_base', 'is_active', 'description',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(FastDlGame::class, 'game_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FastDlFile::class, 'directory_id');
    }

    public function getPathAttribute(): string
    {
        return $this->game->slug . '/' . $this->slug;
    }
}
