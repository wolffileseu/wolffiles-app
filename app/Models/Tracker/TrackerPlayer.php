<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property \Carbon\Carbon|null $first_seen_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property float $elo_rating
 * @property float $elo_peak
 * @property int $id
 * @property string $name
 * @property string $name_clean
 * @property string|null $country_code
 * @property string|null $clan
 */
class TrackerPlayer extends Model
{
    protected $table = 'tracker_players';

    protected $fillable = [
        'guid_hash', 'user_id', 'name', 'name_clean', 'name_html',
        'country', 'country_code',
        'first_seen_at', 'last_seen_at',
        'total_play_time_minutes', 'total_kills', 'total_deaths',
        'total_sessions', 'total_xp',
        'elo_rating', 'elo_peak', 'elo_games', 'level', 'status',
        'claimed_by_user_id', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'elo_rating' => 'decimal:2',
            'elo_peak' => 'decimal:2',
        ];
    }

    public function aliases(): HasMany { return $this->hasMany(TrackerPlayerAlias::class, 'player_id'); }
    public function sessions(): HasMany { return $this->hasMany(TrackerPlayerSession::class, 'player_id'); }
    public function snapshots(): HasMany { return $this->hasMany(TrackerPlayerSnapshot::class, 'player_id'); }
    public function dailyStats(): HasMany { return $this->hasMany(TrackerPlayerDailyStat::class, 'player_id'); }
    public function eloHistory(): HasMany { return $this->hasMany(TrackerEloHistory::class, 'player_id'); }
    public function clanMemberships(): HasMany { return $this->hasMany(TrackerClanMember::class, 'player_id'); }
    public function bans(): HasMany { return $this->hasMany(TrackerBan::class, 'player_id'); }
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(\App\Models\User::class, 'claimed_by_user_id'); }
    public function isClaimedBy(?\App\Models\User $user): bool { return $user && $this->claimed_by_user_id === $user->id; }
    public function isClaimed(): bool { return $this->claimed_by_user_id !== null; }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeTopElo($query) { return $query->orderByDesc('elo_rating'); }

    /**
     * K/D ratio from the Poller pipeline — always 0.0.
     *
     * Quake3's getstatus protocol sends "score ping name" per player — no
     * kills or deaths. Real K/D data is only available through the Enhanced
     * Tracker (ws-packets from sv_tracker2). See the Enhanced Tracker
     * section of a player profile for real per-match K/D.
     *
     * Kept as an accessor returning 0.0 so legacy views/rankings that
     * reference $player->kd_ratio don't break.
     *
     * @deprecated Poller cannot compute K/D — use Enhanced Tracker instead.
     */
    public function getKdRatioAttribute(): float
    {
        return 0.0;
    }

    /**
     * XP earned per hour of play time.
     *
     * Uses total_xp, which is populated from the Quake3 getstatus score field
     * (in vanilla ET, score == XP; mod servers may inflate this metric).
     */
    public function getXpPerHourAttribute(): int
    {
        if ($this->total_play_time_minutes <= 0) {
            return 0;
        }
        return (int) round($this->total_xp / ($this->total_play_time_minutes / 60));
    }

    /**
     * Average XP earned per session. Uses total_xp (populated from score).
     */
    public function getAvgXpPerSessionAttribute(): int
    {
        if ($this->total_sessions <= 0) {
            return 0;
        }
        return (int) round($this->total_xp / $this->total_sessions);
    }

    /**
     * Average session length in minutes.
     */
    public function getAvgSessionMinutesAttribute(): int
    {
        if ($this->total_sessions <= 0) {
            return 0;
        }
        return (int) round($this->total_play_time_minutes / $this->total_sessions);
    }

    public function getPlayTimeFormattedAttribute(): string
    {
        $hours = floor($this->total_play_time_minutes / 60);
        $mins = $this->total_play_time_minutes % 60;
        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }

    public function getActiveClanAttribute(): ?TrackerClan
    {
        $membership = $this->clanMemberships()->where('is_active', true)->with('clan')->first();
        return $membership?->clan;
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned' || $this->bans()->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->exists();
    }

    private ?array $activityStatsCache = null;

    /**
     * Computes activity stats from the session log: heatmap (7x24),
     * peak hour/day, longest + current streak, distinct maps.
     * Memoized per instance.
     */
    public function getActivityStatsAttribute(): array
    {
        if ($this->activityStatsCache !== null) {
            return $this->activityStatsCache;
        }

        // --- Heatmap: sessions grouped by (day_of_week, hour) ---
        // MySQL DAYOFWEEK: 1=Sun..7=Sat. Shift so 0=Mon..6=Sun.
        $heatmapRows = \DB::table('tracker_player_sessions')
            ->where('player_id', $this->id)
            ->whereNotNull('started_at')
            ->selectRaw('((DAYOFWEEK(started_at) + 5) % 7) AS dow, HOUR(started_at) AS hr, COUNT(*) AS c')
            ->groupBy('dow', 'hr')
            ->get();

        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        $peakDow = 0; $peakHr = 0; $peakCount = 0;
        foreach ($heatmapRows as $r) {
            $grid[(int)$r->dow][(int)$r->hr] = (int)$r->c;
            if ((int)$r->c > $peakCount) {
                $peakCount = (int)$r->c;
                $peakDow = (int)$r->dow;
                $peakHr = (int)$r->hr;
            }
        }

        // --- Streaks: distinct play-days, ordered ---
        $days = \DB::table('tracker_player_sessions')
            ->where('player_id', $this->id)
            ->whereNotNull('started_at')
            ->selectRaw('DATE(started_at) AS day')
            ->distinct()
            ->orderBy('day')
            ->pluck('day')
            ->toArray();

        $longest = 0; $current = 0; $prev = null;
        foreach ($days as $day) {
            if ($prev !== null) {
                $diff = (strtotime($day) - strtotime($prev)) / 86400;
                $current = ($diff == 1) ? $current + 1 : 1;
            } else {
                $current = 1;
            }
            if ($current > $longest) {
                $longest = $current;
            }
            $prev = $day;
        }

        // Current streak: only counts if last play-day was today or yesterday
        $currentStreak = 0;
        if (!empty($days)) {
            $lastTs = strtotime(end($days));
            $today = strtotime(date('Y-m-d'));
            $daysSince = ($today - $lastTs) / 86400;
            if ($daysSince <= 1) {
                $currentStreak = $current;
            }
        }

        // --- Distinct maps played ---
        $distinctMaps = \DB::table('tracker_player_sessions')
            ->where('player_id', $this->id)
            ->whereNotNull('map_name')
            ->where('map_name', '!=', '')
            ->distinct('map_name')
            ->count('map_name');

        return $this->activityStatsCache = [
            'heatmap' => $grid,
            'peak' => [
                'dow' => $peakDow,
                'hour' => $peakHr,
                'count' => $peakCount,
            ],
            'streaks' => [
                'longest' => $longest,
                'current' => $currentStreak,
            ],
            'distinct_maps' => $distinctMaps,
            'active_days' => count($days),
        ];
    }

}
