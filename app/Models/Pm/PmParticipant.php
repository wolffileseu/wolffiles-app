<?php

namespace App\Models\Pm;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NOTE: Does NOT use Laravel SoftDeletes trait on purpose.
 * Our `deleted_at` means "user hid from their inbox", not "soft-removed record".
 * We want messages to remain visible to other participants.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $last_read_at
 * @property Carbon|null $deleted_at
 * @property bool $muted
 * @property bool $is_creator
 */
class PmParticipant extends Model
{
    protected $table = "pm_conversation_participants";

    protected $fillable = [
        "conversation_id",
        "user_id",
        "joined_at",
        "left_at",
        "last_read_at",
        "deleted_at",
        "muted",
        "is_creator",
    ];

    protected $casts = [
        "joined_at"    => "datetime",
        "left_at"      => "datetime",
        "last_read_at" => "datetime",
        "deleted_at"   => "datetime",
        "muted"        => "boolean",
        "is_creator"   => "boolean",
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PmConversation::class, "conversation_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull("left_at");
    }

    public function scopeVisibleInInbox(Builder $query): Builder
    {
        return $query->whereNull("deleted_at")->whereNull("left_at");
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function hasUnread(): bool
    {
        if (! $this->relationLoaded("conversation")) {
            $this->load("conversation");
        }

        $convLastMsg = $this->conversation?->last_message_at;

        if (! $convLastMsg) {
            return false;
        }

        return $this->last_read_at === null
            || $this->last_read_at->lt($convLastMsg);
    }
}
