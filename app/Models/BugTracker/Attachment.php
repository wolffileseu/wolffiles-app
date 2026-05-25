<?php

namespace App\Models\BugTracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'bt_attachments';

    protected $fillable = [
        'task_id', 'comment_id', 'uploader_id',
        'original_filename', 'stored_path', 'disk',
        'mime_type', 'size_bytes', 'checksum_sha256',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function getUrlAttribute(): ?string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl($this->stored_path, now()->addMinutes(15));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
