<?php

namespace App\Models\FastDl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $base_directory
 * @property bool $is_active
 * @property int $file_count
 * @property int $total_size
 * @property int $total_dls
 */
class FastDlGame extends Model
{
    protected $table = 'fastdl_games';

    protected $fillable = [
        'name', 'slug', 'base_directory', 'icon', 'is_active',
        'auto_sync', 'wolffiles_game_id', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
    ];

    public function directories(): HasMany
    {
        return $this->hasMany(FastDlDirectory::class, 'game_id');
    }

    public function clans(): HasMany
    {
        return $this->hasMany(FastDlClan::class, 'game_id');
    }

    public function baseDirectory()
    {
        return $this->directories()->where('is_base', true)->first();
    }
}
