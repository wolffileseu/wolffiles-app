<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'user_id', 'action', 'details', 'performed_by', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServerOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(ServerOrder $order, string $action, array $details = [], string $performedBy = 'user'): self
    {
        return static::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
            'performed_by' => $performedBy,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function getActionEmoji(): string
    {
        return match($this->action) {
            'created' => '🆕',
            'started' => '▶️',
            'stopped' => '⏹️',
            'restarted' => '🔄',
            'config_changed' => '⚙️',
            'mod_changed' => '🔧',
            'map_added' => '🗺️',
            'map_removed' => '🗑️',
            'suspended' => '⏸️',
            'terminated' => '❌',
            'renewed' => '💰',
            'backup_created' => '💾',
            'backup_restored' => '📥',
            default => '📋',
        };
    }
}
