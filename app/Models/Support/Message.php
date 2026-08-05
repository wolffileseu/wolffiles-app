<?php

namespace App\Models\Support;

use App\Enums\Support\AuthorType;
use App\Enums\Support\SyncStatus;
use App\Enums\Support\TicketSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'sup_messages';

    protected $fillable = [
        'ticket_id', 'author_type', 'user_id',
        'discord_user_id', 'discord_username', 'guest_email', 'author_name',
        'body', 'body_html', 'is_internal', 'source',
        'discord_message_id', 'email_message_id', 'email_in_reply_to',
        'sync_status', 'sync_error', 'sync_attempts',
        'raw_headers', 'edited_at',
    ];

    protected $casts = [
        'author_type'   => AuthorType::class,
        'source'        => TicketSource::class,
        'sync_status'   => SyncStatus::class,
        'is_internal'   => 'boolean',
        'sync_attempts' => 'integer',
        'edited_at'     => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'message_id');
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_internal', false);
    }

    /**
     * Darf diese Nachricht nach aussen (Discord/Mail) gehen?
     * Interne Notizen sind hier hart ausgeschlossen -- diese Methode ist
     * der einzige Ort, an dem das entschieden wird.
     */
    public function isDeliverable(): bool
    {
        return ! $this->is_internal
            && $this->author_type !== AuthorType::System;
    }

    public function getAuthorLabelAttribute(): string
    {
        return $this->user?->name
            ?? $this->discord_username
            ?? $this->author_name
            ?? $this->guest_email
            ?? 'Unknown';
    }
}
