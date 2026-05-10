<?php

namespace App\Models\Pm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Write-once edit history. We override timestamps because we only want
 * created_at (no updated_at column).
 *
 * @property int $id
 * @property int $message_id
 * @property string $old_body
 * @property \Carbon\Carbon $edited_at
 * @property string|null $edited_from_ip
 * @property \Carbon\Carbon $created_at
 */
class PmMessageEdit extends Model
{
    protected $table = "pm_message_edits";

    public $timestamps = false;

    protected $fillable = [
        "message_id",
        "old_body",
        "edited_at",
        "edited_from_ip",
        "created_at",
    ];

    protected $casts = [
        "edited_at"  => "datetime",
        "created_at" => "datetime",
    ];

    // Write-once enforcement at model level
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException("PmMessageEdit is write-once and cannot be updated.");
        });
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(PmMessage::class, "message_id");
    }
}
