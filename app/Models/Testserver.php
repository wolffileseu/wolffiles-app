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

    public function scopePublic($query)
    {
        return $query->where('enabled', true)->where('public_visible', true);
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
        return $this->connect_ip . ':' . $this->connect_port;
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
