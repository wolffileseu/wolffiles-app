<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Privacy and notification settings per user.
 *
 * @property int $user_id (primary key)
 * @property string $who_can_message everyone|clan_only|nobody
 * @property bool $email_notify
 * @property bool $discord_notify
 * @property bool $telegram_notify
 * @property int $notification_throttle_minutes
 */
class PmUserSetting extends Model
{
    protected $table = "pm_user_settings";

    protected $primaryKey = "user_id";

    public $incrementing = false;

    protected $keyType = "int";

    protected $fillable = [
        "user_id",
        "who_can_message",
        "email_notify",
        "discord_notify",
        "telegram_notify",
        "notification_throttle_minutes",
    ];

    protected $casts = [
        "email_notify"                  => "boolean",
        "discord_notify"                => "boolean",
        "telegram_notify"               => "boolean",
        "notification_throttle_minutes" => "integer",
    ];

    protected $attributes = [
        "who_can_message"               => "everyone",
        "email_notify"                  => true,
        "discord_notify"                => false,
        "telegram_notify"               => false,
        "notification_throttle_minutes" => 15,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    /**
     * Convenience: get-or-create defaults for a user.
     */
    public static function forUser(int $userId): self
    {
        return self::firstOrCreate(["user_id" => $userId]);
    }

    public function allowsMessageFrom(User $sender): bool
    {
        return match ($this->who_can_message) {
            "nobody"    => false,
            "clan_only" => $this->senderInSameClan($sender),
            default     => true,
        };
    }

    /**
     * Stub. Wired up in Phase 3 once we know the clan relation on User.
     */
    protected function senderInSameClan(User $sender): bool
    {
        // TODO: implement in Phase 3 against actual User::clan() relation
        return false;
    }
}
