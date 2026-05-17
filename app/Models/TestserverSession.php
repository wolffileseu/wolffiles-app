<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TestserverSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'testserver_id',
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'country_code',
        'mod_name',
        'map_slug',
        'map_pk3_filename',
        'map_file_id',
        'connect_address',
        'connect_password',
        'status',
        'error_message',
        'reserved_at',
        'started_at',
        'expires_at',
        'ended_at',
        'peak_players',
        'total_player_minutes',
        'snapshot_count',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
        'peak_players' => 'integer',
        'total_player_minutes' => 'integer',
        'snapshot_count' => 'integer',
    ];

    /* ─────────────────────────────────────────
     * Boot — Auto-generate session_token
     * ─────────────────────────────────────────*/

    protected static function booted(): void
    {
        static::creating(function (TestserverSession $session) {
            if (empty($session->session_token)) {
                $session->session_token = (string) Str::uuid();
            }
            if (empty($session->reserved_at)) {
                $session->reserved_at = now();
            }
            if (empty($session->connect_password)) {
                $session->connect_password = Str::lower(Str::random(8));
            }
        });
    }

    /* ─────────────────────────────────────────
     * Relations
     * ─────────────────────────────────────────*/

    public function testserver(): BelongsTo
    {
        return $this->belongsTo(Testserver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mapFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'map_file_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(TestserverPlayerSnapshot::class);
    }

    /* ─────────────────────────────────────────
     * Scopes
     * ─────────────────────────────────────────*/

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'launching', 'active']);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['expired', 'cancelled', 'failed']);
    }

    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /* ─────────────────────────────────────────
     * State Helpers
     * ─────────────────────────────────────────*/

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['expired', 'cancelled', 'failed'], true);
    }

    public function isExpired(): bool
    {
        return $this->status === 'active'
            && $this->expires_at
            && $this->expires_at->isPast();
    }

    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->expires_at || $this->isFinished()) {
            return 0;
        }
        return (int) max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function getDurationSecondsAttribute(): int
    {
        if (!$this->started_at) return 0;
        $end = $this->ended_at ?? now();
        return $this->started_at->diffInSeconds($end);
    }

    /* ─────────────────────────────────────────
     * Connect-String für ET-Client
     * ─────────────────────────────────────────*/

    public function getEtConnectUriAttribute(): string
    {
        return 'et://' . $this->connect_address
            . '/connect;password ' . $this->connect_password;
    }

    public function getRouteKeyName(): string
    {
        return 'session_token';
    }
}
