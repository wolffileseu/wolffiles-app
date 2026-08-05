<?php

namespace App\Models\Support;

use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketSource;
use App\Enums\Support\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $table = 'sup_tickets';

    /** Ticket-Nummern starten bei WF-1000, nicht bei WF-1. */
    public const NUMBER_OFFSET = 1000;

    protected $fillable = [
        'public_id', 'ticket_number', 'category_id', 'subject',
        'status', 'priority', 'source', 'reply_channel',
        'user_id', 'guest_name', 'guest_email', 'guest_token', 'guest_verified_at',
        'discord_user_id', 'discord_username', 'assignee_id',
        'discord_thread_id', 'discord_alert_message_id', 'email_message_id',
        'last_activity_at', 'last_staff_reply_at', 'last_user_reply_at',
        'first_response_at', 'resolved_at', 'closed_at',
        'escalated_at', 'autoclose_warned_at',
        'locale', 'ip_address', 'user_agent', 'meta',
    ];

    protected $casts = [
        'status'              => TicketStatus::class,
        'priority'            => TicketPriority::class,
        'source'              => TicketSource::class,
        'guest_verified_at'   => 'datetime',
        'last_activity_at'    => 'datetime',
        'last_staff_reply_at' => 'datetime',
        'last_user_reply_at'  => 'datetime',
        'first_response_at'   => 'datetime',
        'resolved_at'         => 'datetime',
        'closed_at'           => 'datetime',
        'escalated_at'        => 'datetime',
        'autoclose_warned_at' => 'datetime',
        'ticket_number'       => 'integer',
        'meta'                => 'array',
    ];

    protected $hidden = ['guest_token'];

    protected static function booted(): void
    {
        // Race-frei: Nummer wird aus der bereits vergebenen id abgeleitet,
        // statt max(ticket_number)+1 zu lesen.
        static::created(function (Ticket $ticket) {
            if ($ticket->ticket_number === null) {
                $number = $ticket->id + self::NUMBER_OFFSET;
                $ticket->forceFill([
                    'ticket_number' => $number,
                    'public_id'     => 'WF-'.$number,
                ])->saveQuietly();
            }
        });
    }

    // ---------------------------------------------------------------- Relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'ticket_id')->orderBy('created_at');
    }

    /** Nur die Nachrichten, die der Ticket-Ersteller sehen darf. */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'ticket_id');
    }

    // ---------------------------------------------------------------- Scopes

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', ['new', 'open', 'pending', 'on_hold']);
    }

    public function scopeUnassigned(Builder $q): Builder
    {
        return $q->whereNull('assignee_id');
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->open()->whereNull('escalated_at')
            ->where('last_activity_at', '<', now()->subHours(24));
    }

    // ---------------------------------------------------------------- Helpers

    public function getDisplayIdAttribute(): string
    {
        return $this->public_id ?? 'WF-?';
    }

    /** Anzeigename des Erstellers, egal ueber welchen Kanal er kam. */
    public function getRequesterNameAttribute(): string
    {
        return $this->user?->name
            ?? $this->discord_username
            ?? $this->guest_name
            ?? $this->guest_email
            ?? 'Unknown';
    }

    public function isGuest(): bool
    {
        return $this->user_id === null && $this->discord_user_id === null;
    }

    /** Gast-Tickets sind erst nach Double-Opt-In sichtbar/aktiv. */
    public function needsVerification(): bool
    {
        return $this->guest_email !== null
            && $this->user_id === null
            && $this->guest_verified_at === null;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
