<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $message_id
 * @property string $type
 * @property string $storage_disk
 * @property string $storage_path
 * @property string|null $thumbnail_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $file_size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property \Carbon\Carbon $uploaded_at
 * @property \Carbon\Carbon|null $purged_at
 */
class PmAttachment extends Model
{
    protected $table = "pm_message_attachments";

    protected $fillable = [
        "message_id",
        "type",
        "storage_disk",
        "storage_path",
        "thumbnail_path",
        "original_filename",
        "mime_type",
        "file_size_bytes",
        "width",
        "height",
        "uploaded_at",
        "purged_at",
    ];

    protected $casts = [
        "uploaded_at"     => "datetime",
        "purged_at"       => "datetime",
        "file_size_bytes" => "integer",
        "width"           => "integer",
        "height"          => "integer",
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(PmMessage::class, "message_id");
    }

    public function scopeNotPurged(Builder $query): Builder
    {
        return $query->whereNull("purged_at");
    }

    public function isPurged(): bool
    {
        return $this->purged_at !== null;
    }

    public function getUrl(): ?string
    {
        if ($this->isPurged()) {
            return null;
        }
        return Storage::disk($this->storage_disk)->url($this->storage_path);
    }

    public function getThumbnailUrl(): ?string
    {
        if ($this->isPurged() || ! $this->thumbnail_path) {
            return null;
        }
        return Storage::disk($this->storage_disk)->url($this->thumbnail_path);
    }
}
