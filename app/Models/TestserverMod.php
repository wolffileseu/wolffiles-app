<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestserverMod extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'display_name',
        'short_description',
        'fastdl_archive_path',
        'fastdl_archive_sha256',
        'default_config_file',
        'fs_game_dir',
        'enabled',
        'show_on_public',
        'sort_order',
        'notes',
        'compatible_egg_ids',
    ];

    protected $casts = [
        'enabled'            => 'boolean',
        'show_on_public'     => 'boolean',
        'sort_order'         => 'integer',
        'compatible_egg_ids' => 'array',
    ];

    public function scopeEnabled($q)
    {
        return $q->where('enabled', true);
    }

    public function scopePublic($q)
    {
        return $q->where('enabled', true)->where('show_on_public', true);
    }

    public function scopeForEgg($q, int $eggId)
    {
        return $q->where(function ($q2) use ($eggId) {
            $q2->whereNull('compatible_egg_ids')
               ->orWhereJsonContains('compatible_egg_ids', $eggId);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Braucht der Mod einen FastDL-Download? (sonst ist er schon im Container) */
    public function needsFastdlDownload(): bool
    {
        return !empty($this->fastdl_archive_path);
    }

    public function getFastdlUrlAttribute(): ?string
    {
        if (!$this->fastdl_archive_path) return null;
        return 'https://dl.wolffiles.eu/et/' . ltrim($this->fastdl_archive_path, '/');
    }
}
