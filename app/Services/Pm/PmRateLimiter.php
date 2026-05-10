<?php

namespace App\Services\Pm;

use App\Exceptions\Pm\RateLimitExceededException;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

/**
 * Rate limiter for PM actions, backed by Redis.
 *
 * Storage: Redis keys "pm_rl:{user_id}:{action}:{window}:{slot}"
 *   window = "hour" | "day"
 *   slot   = unix timestamp truncated to window start
 *
 * Each hit increments the counter; TTL is set on first creation.
 *
 * Limits live in config/pm.php under rate_limits, per-role.
 *
 * The audit table pm_rate_limits is NOT touched here for performance;
 * write-through to that table is optional and handled by PmService when
 * a hit triggers an exception (so we have a record of throttling events).
 */
class PmRateLimiter
{
    private const KEY_PREFIX = "pm_rl";

    /**
     * Increment the counter and throw if over the limit.
     * Call this when actually performing the action (after authorization).
     */
    public function hit(User $user, string $action): void
    {
        $limits = $this->limitsFor($user, $action);

        foreach (["hour", "day"] as $window) {
            $limit = $limits[$window] ?? null;
            if ($limit === null) {
                continue;
            }

            [$key, $ttl, $slotEndsAt] = $this->keyFor($user->id, $action, $window);

            $count = (int) Redis::incr($key);
            if ($count === 1) {
                Redis::expire($key, $ttl);
            }

            if ($count > $limit) {
                $retryAfter = max(1, $slotEndsAt - time());
                throw new RateLimitExceededException(
                    $action,
                    $window,
                    $limit,
                    $retryAfter
                );
            }
        }
    }

    /**
     * Check without incrementing. Returns true if within all limits.
     */
    public function check(User $user, string $action): bool
    {
        $limits = $this->limitsFor($user, $action);

        foreach (["hour", "day"] as $window) {
            $limit = $limits[$window] ?? null;
            if ($limit === null) {
                continue;
            }

            [$key,] = $this->keyFor($user->id, $action, $window);
            $count = (int) (Redis::get($key) ?? 0);

            if ($count >= $limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get remaining count and reset time for each window.
     * Useful for UI hints ("you have X messages left this hour").
     *
     * @return array<string, array{used:int, limit:int, remaining:int, resets_in_seconds:int}>
     */
    public function getStatus(User $user, string $action): array
    {
        $limits = $this->limitsFor($user, $action);
        $status = [];

        foreach (["hour", "day"] as $window) {
            $limit = $limits[$window] ?? null;
            if ($limit === null) {
                continue;
            }

            [$key, , $slotEndsAt] = $this->keyFor($user->id, $action, $window);
            $count = (int) (Redis::get($key) ?? 0);

            $status[$window] = [
                "used"              => $count,
                "limit"             => $limit,
                "remaining"         => max(0, $limit - $count),
                "resets_in_seconds" => max(0, $slotEndsAt - time()),
            ];
        }

        return $status;
    }

    /**
     * Manual reset, for tests or moderator override.
     */
    public function reset(User $user, string $action): void
    {
        foreach (["hour", "day"] as $window) {
            [$key,] = $this->keyFor($user->id, $action, $window);
            Redis::del($key);
        }
    }

    // -----------------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------------

    /**
     * Resolve which role bucket applies to this user.
     * Order: admin > moderator > default.
     */
    private function limitsFor(User $user, string $action): array
    {
        $allLimits = config("pm.rate_limits", []);

        $roleKey = "default";
        if ($user->hasRole("admin")) {
            $roleKey = "admin";
        } elseif ($user->hasRole("moderator")) {
            $roleKey = "moderator";
        }

        return $allLimits[$roleKey][$action]
            ?? $allLimits["default"][$action]
            ?? [];
    }

    /**
     * Build the Redis key, TTL, and slot-end-unix-timestamp for a given window.
     *
     * @return array{0:string, 1:int, 2:int}  [key, ttl_seconds, slot_ends_at]
     */
    private function keyFor(int $userId, string $action, string $window): array
    {
        $now = time();

        if ($window === "hour") {
            $slotStart = $now - ($now % 3600);
            $ttl       = 3600;
        } else {
            // day, aligned to UTC midnight
            $slotStart = $now - ($now % 86400);
            $ttl       = 86400;
        }

        $key = sprintf("%s:%d:%s:%s:%d", self::KEY_PREFIX, $userId, $action, $window, $slotStart);

        return [$key, $ttl, $slotStart + $ttl];
    }
}
