<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $reporter_id
 * @property int $message_id
 * @property int $conversation_id
 * @property string $reason_code
 * @property string|null $reason_text
 * @property string $status open|reviewing|resolved|dismissed
 * @property int|null $resolved_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property string|null $resolution_note
 */
class PmMessageReport extends Model
{
    protected $table = "pm_message_reports";

    public const REASON_CODES = [
        "spam",
        "harassment",
        "illegal",
        "threat",
        "other",
    ];

    public const STATUS_OPEN      = "open";
    public const STATUS_REVIEWING = "reviewing";
    public const STATUS_RESOLVED  = "resolved";
    public const STATUS_DISMISSED = "dismissed";

    protected $fillable = [
        "reporter_id",
        "message_id",
        "conversation_id",
        "reason_code",
        "reason_text",
        "status",
        "resolved_by",
        "resolved_at",
        "resolution_note",
    ];

    protected $casts = [
        "resolved_at" => "datetime",
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, "reporter_id");
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(PmMessage::class, "message_id");
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PmConversation::class, "conversation_id");
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, "resolved_by");
    }

    public function evidenceSnapshots(): HasMany
    {
        return $this->hasMany(PmEvidenceSnapshot::class, "related_report_id");
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where("status", self::STATUS_OPEN);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn("status", [self::STATUS_OPEN, self::STATUS_REVIEWING]);
    }
}
