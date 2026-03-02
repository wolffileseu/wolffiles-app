<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerProduct extends Model
{
    protected $fillable = [
        'name', 'slug', 'game', 'slots', 'min_slots', 'max_slots',
        'memory_mb', 'memory_per_slot_mb', 'base_memory_mb',
        'cpu_percent', 'cpu_per_slot_percent',
        'disk_mb', 'disk_per_slot_mb', 'base_disk_mb',
        'price_daily', 'price_weekly', 'price_monthly', 'price_quarterly',
        'price_per_slot_daily', 'price_per_slot_weekly', 'price_per_slot_monthly', 'price_per_slot_quarterly',
        'description', 'features', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price_daily' => 'decimal:2',
        'price_weekly' => 'decimal:2',
        'price_monthly' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_per_slot_daily' => 'decimal:4',
        'price_per_slot_weekly' => 'decimal:4',
        'price_per_slot_monthly' => 'decimal:4',
        'price_per_slot_quarterly' => 'decimal:4',
        'memory_per_slot_mb' => 'decimal:2',
        'cpu_per_slot_percent' => 'decimal:2',
        'disk_per_slot_mb' => 'decimal:2',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(ServerOrder::class, 'product_id');
    }

    // ==========================================
    // SLOT-BASIERTE PREISBERECHNUNG
    // ==========================================

    /**
     * Preis für bestimmte Slot-Anzahl und Periode
     */
    public function calculatePrice(int $slots, string $period = 'monthly'): float
    {
        $slots = max($this->min_slots, min($this->max_slots, $slots));

        $pricePerSlot = match($period) {
            'daily' => $this->price_per_slot_daily,
            'weekly' => $this->price_per_slot_weekly,
            'monthly' => $this->price_per_slot_monthly,
            'quarterly' => $this->price_per_slot_quarterly,
            default => $this->price_per_slot_monthly,
        };

        return round($slots * (float) $pricePerSlot, 2);
    }

    /**
     * Alle Preise für eine Slot-Anzahl
     */
    public function calculatePrices(int $slots): array
    {
        return [
            'daily' => $this->calculatePrice($slots, 'daily'),
            'weekly' => $this->calculatePrice($slots, 'weekly'),
            'monthly' => $this->calculatePrice($slots, 'monthly'),
            'quarterly' => $this->calculatePrice($slots, 'quarterly'),
        ];
    }

    /**
     * Preis pro Slot pro Monat (für Anzeige)
     */
    public function getPricePerSlotMonthly(): float
    {
        return (float) $this->price_per_slot_monthly;
    }

    // ==========================================
    // RESOURCE-BERECHNUNG PRO SLOTS
    // ==========================================

    /**
     * RAM für bestimmte Slot-Anzahl
     */
    public function calculateMemory(int $slots): int
    {
        return (int) ($this->base_memory_mb + ($slots * (float) $this->memory_per_slot_mb));
    }

    /**
     * CPU für bestimmte Slot-Anzahl
     */
    public function calculateCpu(int $slots): int
    {
        return (int) ($slots * (float) $this->cpu_per_slot_percent);
    }

    /**
     * Disk für bestimmte Slot-Anzahl
     */
    public function calculateDisk(int $slots): int
    {
        return (int) ($this->base_disk_mb + ($slots * (float) $this->disk_per_slot_mb));
    }

    /**
     * Alle Ressourcen für Slots
     */
    public function calculateResources(int $slots): array
    {
        return [
            'memory_mb' => $this->calculateMemory($slots),
            'cpu_percent' => $this->calculateCpu($slots),
            'disk_mb' => $this->calculateDisk($slots),
        ];
    }

    // ==========================================
    // LEGACY SUPPORT (feste Paket-Preise als Fallback)
    // ==========================================

    public function getPriceForPeriod(string $period, int $slots = null): float
    {
        if ($slots) {
            return $this->calculatePrice($slots, $period);
        }

        return match($period) {
            'daily' => (float) $this->price_daily,
            'weekly' => (float) $this->price_weekly,
            'monthly' => (float) $this->price_monthly,
            'quarterly' => (float) $this->price_quarterly,
            default => (float) $this->price_monthly,
        };
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGame($query, string $game)
    {
        return $query->where('game', $game);
    }
}
