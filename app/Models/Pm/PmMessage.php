<?php

namespace App\Models\Pm;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string|null $body
 * @property string $body_format markdown|plain
 * @property Carbon|null $body_purged_at
 * @property Carbon|null $edited_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class PmMessage extends Model
{
    protected $table = "pm_messages";

    protected $fillable = [
        "conversation_id",
        "sender_id",
        "body",
        "body_format",
        "body_purged_at",
        "edited_at",
        "ip_address",
        "user_agent",
    ];

    protected $casts = [
        "body_purged_at" => "datetime",
        "edited_at"      => "datetime",
    ];

    // -----------------------------------------------------------------------
    // Boot: maintain conversation counter cache
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::created(function (PmMessage $message) {
            // Increment counter and bump last_message_at on the conversation
            PmConversation::where("id", $message->conversation_id)
                ->update([
                    "message_count"   => DB::raw("message_count + 1"),
                    "last_message_at" => $message->created_at ?? now(),
                ]);
        });

        static::deleted(function (PmMessage $message) {
            // Decrement counter on hard delete (rare; mostly retention nulls body)
            PmConversation::where("id", $message->conversation_id)
                ->where("message_count", ">", 0)
                ->update([
                    "message_count" => DB::raw("message_count - 1"),
                ]);
        });
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PmConversation::class, "conversation_id");
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, "sender_id");
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PmAttachment::class, "message_id");
    }

    public function edits(): HasMany
    {
        return $this->hasMany(PmMessageEdit::class, "message_id");
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PmMessageReport::class, "message_id");
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeNotPurged(Builder $query): Builder
    {
        return $query->whereNull("body_purged_at");
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isPurged(): bool
    {
        return $this->body_purged_at !== null;
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }
}
