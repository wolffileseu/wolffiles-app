<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Testserver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'slot_number',
        'pterodactyl_uuid',
        'pterodactyl_server_id',
        'pterodactyl_egg_id',
        'connect_ip',
        'connect_port',
        'default_mod',
        'allowed_mod_slugs',
        'default_map',
        'default_config',
        'max_session_minutes',
        'max_players',
        'enabled',
        'public_visible',
        'status',
        'last_status_check_at',
        'last_error',
        'total_sessions',
        'last_session_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'public_visible' => 'boolean',
        'last_status_check_at' => 'datetime',
        'last_session_at' => 'datetime',
        'slot_number' => 'integer',
        'connect_port' => 'integer',
        'max_session_minutes' => 'integer',
        'max_players' => 'integer',
        'total_sessions' => 'integer',
        'pterodactyl_server_id' => 'integer',
        'pterodactyl_egg_id' => 'integer',
        'allowed_mod_slugs' => 'array',
        'last_rotation_at' => 'datetime',
    ];

    /* ─────────────────────────────────────────
     * Relations
     * ─────────────────────────────────────────*/

    public function sessions(): HasMany
    {
        return $this->hasMany(TestserverSession::class);
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(TestserverSession::class)
            ->whereIn('status', ['pending', 'launching', 'active'])
            ->latestOfMany();
    }

    /* ─────────────────────────────────────────
     * Scopes
     * ─────────────────────────────────────────*/

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }


    /**
     * Auto-Onboarding: Wenn Server enabled+public_visible wird (z.B. via Filament),
     * automatisch in Idle-Mode bringen + Tracker-Discovery triggern.
     */
    protected static function booted(): void
    {
        static::saved(function (self $server) {
            // Nur wenn enabled+public und gerade nicht in active-session
            if (!$server->enabled || !$server->public_visible) {
                return;
            }
            if (in_array($server->status, ['active', 'reserving', 'expiring'], true)) {
                return;
            }

            // Nur reagieren wenn enabled oder public_visible jetzt erst gesetzt wurde
            $changed = $server->wasChanged(['enabled', 'public_visible']);
            if (!$changed) return;

            \Log::info('Testserver auto-onboard triggered', [
                'slot' => $server->slot_number,
                'name' => $server->name,
            ]);

            // Idle-Mode triggern (async via Queue, nicht blockierend)
            dispatch(function () use ($server) {
                $svc = app(\App\Services\TestserverService::class);
                $svc->enterIdleMode($server);

                // Tracker-Discovery so dass Server in deinem eigenen Tracker auftaucht
                \Illuminate\Support\Facades\Artisan::call('tracker:discover-servers');
            })->onQueue('default')->afterCommit();
        });
    }

    public function scopePublic($query)
    {
        // Admin sieht auch hidden server, alle anderen nur public_visible
        if (!(auth()->user()?->isAdmin() ?? false)) {
            $query->where('public_visible', true);
        }
        return $query->where('enabled', true);
    }

    public function scopeAvailable($query)
    {
        return $query->enabled()->where('status', 'idle');
    }

    /* ─────────────────────────────────────────
     * Helpers
     * ─────────────────────────────────────────*/

    public function isAvailable(): bool
    {
        return $this->enabled && $this->status === 'idle';
    }

    public function isBusy(): bool
    {
        return in_array($this->status, ['reserving', 'active', 'cleanup'], true);
    }

    public function getConnectStringAttribute(): string
    {
        return ($this->connect_hostname ?: $this->connect_ip) . ':' . $this->connect_port;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'idle'        => '🟢',
            'reserving'   => '🟡',
            'active'      => '🔴',
            'cleanup'     => '🟠',
            'offline'     => '⚫',
            'maintenance' => '🔧',
            default       => '❓',
        };
    }


    /**
     * Liefert die für diesen Server erlaubten Mods.
     * Wenn allowed_mod_slugs leer/null → alle aktivierten Mods.
     */
    public function allowedMods()
    {
        $query = TestserverMod::public()
            ->forEgg($this->pterodactyl_egg_id)
            ->orderBy('sort_order');

        if (!empty($this->allowed_mod_slugs) && is_array($this->allowed_mod_slugs)) {
            $query->whereIn('slug', $this->allowed_mod_slugs);
        }

        return $query->get();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
