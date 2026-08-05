<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $table = 'sup_attachments';

    protected $fillable = [
        'ticket_id', 'message_id', 'disk', 'path',
        'original_name', 'mime_type', 'size', 'checksum',
        'uploaded_by', 'source',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Zeitlich begrenzte URL -- Anhaenge sind nie oeffentlich lesbar. */
    public function temporaryUrl(int $minutes = 15): ?string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes($minutes));
        } catch (\Throwable) {
            return null;
        }
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
