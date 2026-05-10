<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Write-once evidence snapshot of a conversation, frozen at point-in-time
 * for legal/moderation purposes. Hash field allows integrity verification.
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $snapshot_data JSON: full conv + participants + messages
 * @property string $snapshot_hash sha256 of snapshot_data
 * @property string $reason
 * @property int|null $related_report_id
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 */
class PmEvidenceSnapshot extends Model
{
    protected $table = "pm_evidence_snapshots";

    public $timestamps = false;

    protected $fillable = [
        "conversation_id",
        "snapshot_data",
        "snapshot_hash",
        "reason",
        "related_report_id",
        "created_by",
        "created_at",
    ];

    protected $casts = [
        "created_at" => "datetime",
    ];

    // Write-once enforcement
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException("PmEvidenceSnapshot is write-once and cannot be updated.");
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PmConversation::class, "conversation_id");
    }

    public function relatedReport(): BelongsTo
    {
        return $this->belongsTo(PmMessageReport::class, "related_report_id");
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by");
    }

    /**
     * Verify the stored hash matches the snapshot_data.
     * Used to prove integrity in legal contexts.
     */
    public function verifyIntegrity(): bool
    {
        return hash("sha256", $this->snapshot_data) === $this->snapshot_hash;
    }

    /**
     * Decode the snapshot JSON back into an array.
     */
    public function getDataArray(): array
    {
        return json_decode($this->snapshot_data, true) ?? [];
    }

    /**
     * Compute hash for given JSON string. Used during creation.
     */
    public static function hashData(string $jsonData): string
    {
        return hash("sha256", $jsonData);
    }
}
