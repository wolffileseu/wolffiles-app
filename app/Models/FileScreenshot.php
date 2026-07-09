<?php

namespace App\Models;

use Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $file_id
 * @property string|null $path
 * @property string|null $thumbnail_path
 * @property string|null $url
 * @property bool $is_primary
 * @property int $sort_order
 */
class FileScreenshot extends Model
{
    protected $fillable = [
        'file_id', 'path', 'thumbnail_path', 'source',
        'original_name', 'sort_order', 'is_primary', 'width', 'height',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('s3')->url($this->path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return Storage::disk('s3')->url($this->thumbnail_path ?? $this->path);
    }
}
