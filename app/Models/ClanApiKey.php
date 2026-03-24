<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClanApiKey extends Model
{
    protected $fillable = [
        'clan_id', 'key', 'label', 'is_active', 'last_used_at', 'expires_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['key'];

    public static function generate(int $clanId, string $label = null): self
    {
        return self::create([
            'clan_id'   => $clanId,
            'key'       => Str::random(48),
            'label'     => $label,
            'is_active' => true,
        ]);
    }

    public function clan()
    {
        return $this->belongsTo(Clan::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
