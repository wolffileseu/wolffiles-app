<?php

namespace App\Models\Pm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rate limit tracking. Logic lives in PmRateLimiter service (Phase 3).
 *
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property int $count
 * @property \Carbon\Carbon $window_start
 */
class PmRateLimit extends Model
{
    protected $table = "pm_rate_limits";

    public const ACTION_NEW_CONVERSATION = "new_conversation";
    public const ACTION_SEND_MESSAGE     = "send_message";

    protected $fillable = [
        "user_id",
        "action",
        "count",
        "window_start",
    ];

    protected $casts = [
        "window_start" => "datetime",
        "count"        => "integer",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
