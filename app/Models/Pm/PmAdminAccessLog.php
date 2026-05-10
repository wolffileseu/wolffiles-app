<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Write-once audit log: every admin/mod access to PM content.
 *
 * @property int $id
 * @property int $admin_id
 * @property int|null $conversation_id
 * @property int|null $message_id
 * @property string $action
 * @property string $reason
 * @property string|null $admin_ip
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class PmAdminAccessLog extends Model
{
    protected $table = "pm_admin_access_log";

    public $timestamps = false;

    public const ACTIONS = [
        "view_inbox",
        "view_conversation",
        "view_message",
        "create_snapshot",
        "export",
        "lock",
        "unlock",
        "resolve_report",
    ];

    protected $fillable = [
        "admin_id",
        "conversation_id",
        "message_id",
        "action",
        "reason",
        "admin_ip",
        "user_agent",
        "created_at",
    ];

    protected $casts = [
        "created_at" => "datetime",
    ];

    // Write-once enforcement
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException("PmAdminAccessLog is write-once and cannot be updated.");
        });

        static::deleting(function () {
            throw new \RuntimeException("PmAdminAccessLog records cannot be deleted.");
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, "admin_id");
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PmConversation::class, "conversation_id");
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(PmMessage::class, "message_id");
    }
}
