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

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            // Interne Notizen werden nie ausgeliefert -- hier, nicht erst im Job.
            if ($message->is_internal) {
                $message->sync_status = SyncStatus::Skipped;
            }
        });

        // Ticket-Zeitstempel mitziehen, damit SLA und Sortierung stimmen.
        static::created(function (Message $message) {
            $ticket = $message->ticket;

            if (! $ticket) {
                return;
            }

            $attributes = ['last_activity_at' => $message->created_at];

            if ($message->author_type === AuthorType::Staff && ! $message->is_internal) {
                $attributes['last_staff_reply_at'] = $message->created_at;

                if ($ticket->first_response_at === null) {
                    $attributes['first_response_at'] = $message->created_at;
                }
            }

            if (in_array($message->author_type, [AuthorType::User, AuthorType::Guest, AuthorType::Discord, AuthorType::Email], true)) {
                $attributes['last_user_reply_at'] = $message->created_at;
                // Neue User-Antwort -> Eskalationssperre aufheben
                $attributes['escalated_at']        = null;
                $attributes['autoclose_warned_at'] = null;
            }

            $ticket->forceFill($attributes)->saveQuietly();
        });
    }

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
