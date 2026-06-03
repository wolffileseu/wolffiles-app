<?php

namespace App\Models\Tracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PlayerScreenshot extends Model
{
    protected $table = 'tracker_player_screenshots';

    protected $fillable = [
        'player_id', 'uploaded_by_user_id', 'file_path', 'file_size',
        'mime_type', 'width', 'height', 'title', 'caption',
        'is_public', 'sort_order',
    ];

    protected $casts = [
        'is_public'   => 'boolean',
        'file_size'   => 'integer',
        'width'       => 'integer',
        'height'      => 'integer',
        'sort_order'  => 'integer',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(TrackerPlayer::class, 'player_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /** Public-facing URL through the CDN. */
    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return Storage::disk('s3')->url($this->file_path);
    }

    /** Human-readable size for the UI. */
    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
        return number_format($bytes / 1024 / 1024, 1) . ' MB';
    }
}
