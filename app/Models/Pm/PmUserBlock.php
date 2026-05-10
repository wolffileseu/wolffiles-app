<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $blocker_id
 * @property int $blocked_id
 * @property string|null $reason
 */
class PmUserBlock extends Model
{
    protected $table = "pm_user_blocks";

    protected $fillable = [
        "blocker_id",
        "blocked_id",
        "reason",
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, "blocker_id");
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, "blocked_id");
    }

    /**
     * Quick check: is $blockedId blocked by $blockerId?
     */
    public static function exists_(int $blockerId, int $blockedId): bool
    {
        return self::where("blocker_id", $blockerId)
            ->where("blocked_id", $blockedId)
            ->exists();
    }
}
