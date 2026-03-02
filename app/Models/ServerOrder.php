<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $node_id
 * @property int|null $product_id
 * @property int|null $pterodactyl_server_id
 * @property string $status
 * @property string $server_name
 * @property-read \App\Models\ServerNode|null $node
 * @property-read \App\Models\ServerProduct|null $product
 * @property-read \App\Models\User|null $user
 */
class ServerOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'product_id', 'server_name', 'game', 'mod', 'slots',
        'pterodactyl_server_id', 'pterodactyl_user_id', 'status',
        'ip_address', 'port', 'rcon_password', 'server_password',
        'billing_period', 'price_paid', 'paid_until', 'auto_renew',
        'node', 'config', 'suspended_at', 'terminated_at', 'last_status_check',
    ];

    protected $casts = [
        'config' => 'array',
        'price_paid' => 'decimal:2',
        'paid_until' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'last_status_check' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    protected $hidden = ['rcon_password', 'server_password'];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ServerProduct::class, 'product_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(ServerNode::class, 'node_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(ServerInvoice::class, 'order_id');
    }

    public function backups(): HasMany
    {
        return $this->hasMany(ServerBackup::class, 'order_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ServerActivityLog::class, 'order_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->where('paid_until', '<=', now()->addDays($days))
            ->where('paid_until', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('paid_until', '<', now());
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->paid_until > now();
    }

    public function isExpired(): bool
    {
        return $this->paid_until && $this->paid_until < now();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function daysRemaining(): int
    {
        if (!$this->paid_until) return 0;
        return max(0, (int) now()->diffInDays($this->paid_until, false));
    }

    public function getConnectUrl(): string
    {
        return "et://{$this->ip_address}:{$this->port}";
    }

    public function getModDisplayName(): string
    {
        return match($this->mod) {
            'etmain' => 'Vanilla',
            'etpro' => 'ETPro',
            'jaymod' => 'jaymod',
            'nitmod' => 'N!tmod',
            'noquarter' => 'NoQuarter',
            'silent' => 'Silent Mod',
            'legacy' => 'Legacy Mod',
            default => $this->mod,
        };
    }

    public function getGameDisplayName(): string
    {
        return match($this->game) {
            'et' => 'Enemy Territory 2.60b',
            'etl' => 'ET: Legacy',
            'rtcw' => 'Return to Castle Wolfenstein',
            default => $this->game,
        };
    }

    public function getStatusBadge(): array
    {
        return match($this->status) {
            'active' => ['label' => 'Online', 'color' => 'success'],
            'provisioning' => ['label' => 'Wird erstellt...', 'color' => 'warning'],
            'suspended' => ['label' => 'Suspendiert', 'color' => 'danger'],
            'terminated' => ['label' => 'Beendet', 'color' => 'gray'],
            'error' => ['label' => 'Fehler', 'color' => 'danger'],
            'pending' => ['label' => 'Ausstehend', 'color' => 'warning'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }
}
