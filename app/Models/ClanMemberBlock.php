<?php

namespace App\Models;

use App\Models\Tracker\TrackerPlayer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClanMemberBlock extends Model
{
    use HasFactory;

    const TYPE_PLAYER_ID = 'player_id';
    const TYPE_NAME = 'name';

    protected $fillable = [
        'clan_id',
        'block_type',
        'target_player_id',
        'target_name',
        'blocked_by_user_id',
        'reason',
    ];

    public function clan(): BelongsTo
    {
        return $this->belongsTo(Clan::class);
    }

    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(TrackerPlayer::class, 'target_player_id');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by_user_id');
    }

    /** Helper: check if a player_id is blocked for a clan. */
    public static function isPlayerBlocked(int $clanId, int $playerId): bool
    {
        return static::where('clan_id', $clanId)
            ->where('block_type', self::TYPE_PLAYER_ID)
            ->where('target_player_id', $playerId)
            ->exists();
    }

    /** Helper: check if a name is blocked for a clan (exact match, case-insensitive). */
    public static function isNameBlocked(int $clanId, string $name): bool
    {
        return static::where('clan_id', $clanId)
            ->where('block_type', self::TYPE_NAME)
            ->whereRaw('LOWER(target_name) = LOWER(?)', [$name])
            ->exists();
    }
}
