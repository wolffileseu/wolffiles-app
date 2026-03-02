<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $memory_total_mb
 * @property int $memory_allocated_mb
 * @property int $active_servers
 * @property bool $is_active
 */
class ServerNode extends Model
{
    protected $fillable = [
        'name', 'pterodactyl_node_id', 'location', 'fqdn',
        'memory_total_mb', 'memory_allocated_mb', 'disk_total_mb', 'disk_allocated_mb',
        'max_servers', 'active_servers', 'is_active', 'is_full',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_full' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(ServerOrder::class, 'node_id');
    }

    public function activeOrders(): HasMany
    {
        return $this->orders()->where('status', 'active');
    }

    public function memoryAvailableMb(): int
    {
        return max(0, $this->memory_total_mb - $this->memory_allocated_mb);
    }

    public function diskAvailableMb(): int
    {
        return max(0, $this->disk_total_mb - $this->disk_allocated_mb);
    }

    public function canFit(ServerProduct $product): bool
    {
        return $this->is_active
            && !$this->is_full
            && $this->active_servers < $this->max_servers
            && $this->memoryAvailableMb() >= $product->memory_mb
            && $this->diskAvailableMb() >= $product->disk_mb;
    }

    public function allocate(ServerProduct $product): void
    {
        $this->increment('memory_allocated_mb', $product->memory_mb);
        $this->increment('disk_allocated_mb', $product->disk_mb);
        $this->increment('active_servers');

        if ($this->active_servers >= $this->max_servers || $this->memoryAvailableMb() < 256) {
            $this->update(['is_full' => true]);
        }
    }

    public function deallocate(ServerProduct $product): void
    {
        $this->decrement('memory_allocated_mb', min($product->memory_mb, $this->memory_allocated_mb));
        $this->decrement('disk_allocated_mb', min($product->disk_mb, $this->disk_allocated_mb));
        $this->decrement('active_servers', min(1, $this->active_servers));
        $this->update(['is_full' => false]);
    }

    public static function findBestNode(ServerProduct $product): ?self
    {
        return static::where('is_active', true)
            ->where('is_full', false)
            ->get()
            ->filter(fn ($node) => $node->canFit($product))
            ->sortBy('active_servers')  // Least loaded first
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
