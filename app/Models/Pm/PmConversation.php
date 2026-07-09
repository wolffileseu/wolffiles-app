<?php

namespace App\Models\Pm;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string|null $subject
 * @property string $type direct|group
 * @property string|null $hash_key
 * @property int $created_by
 * @property Carbon|null $last_message_at
 * @property int $message_count
 * @property bool $locked
 */
class PmConversation extends Model
{
    protected $table = "pm_conversations";

    protected $fillable = [
        "subject",
        "type",
        "hash_key",
        "created_by",
        "last_message_at",
        "message_count",
        "locked",
    ];

    protected $casts = [
        "last_message_at" => "datetime",
        "locked"          => "boolean",
        "message_count"   => "integer",
    ];

    // -----------------------------------------------------------------------
    // Hash helper for 1:1 dedup
    // -----------------------------------------------------------------------

    /**
     * Compute the deterministic hash for a 1:1 conversation between user IDs.
     * Returns null when more than 2 IDs are passed (group conversations have no hash).
     *
     * @param array<int> $userIds
     */
    public static function hashFor(array $userIds): ?string
    {
        if (count($userIds) !== 2) {
            return null;
        }

        $ids = array_map("intval", $userIds);
        sort($ids, SORT_NUMERIC);

        return hash("sha256", $ids[0] . "|" . $ids[1]);
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function participants(): HasMany
    {
        return $this->hasMany(PmParticipant::class, "conversation_id");
    }

    public function activeParticipants(): HasMany
    {
        return $this->hasMany(PmParticipant::class, "conversation_id")
            ->whereNull("left_at");
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PmMessage::class, "conversation_id");
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(PmMessage::class, "conversation_id")
            ->latestOfMany();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PmMessageReport::class, "conversation_id");
    }

    public function evidenceSnapshots(): HasMany
    {
        return $this->hasMany(PmEvidenceSnapshot::class, "conversation_id");
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeDirect(Builder $query): Builder
    {
        return $query->where("type", "direct");
    }

    public function scopeGroup(Builder $query): Builder
    {
        return $query->where("type", "group");
    }

    public function scopeNotLocked(Builder $query): Builder
    {
        return $query->where("locked", false);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isDirect(): bool
    {
        return $this->type === "direct";
    }

    public function isGroup(): bool
    {
        return $this->type === "group";
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->participants()
            ->where("user_id", $userId)
            ->whereNull("left_at")
            ->exists();
    }
}
