<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class NdaInvitation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'token_hash',
        'nda_template_id',
        'locale',
        'role_name',
        'role_names',
        'permissions',
        'penalty_amount',
        'log_retention_months',
        'authoritative_language',
        'recipient_label',
        'recipient_email',
        'note',
        'user_id',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'role_names' => 'array',
            'permissions' => 'array',
            'penalty_amount' => 'decimal:2',
            'log_retention_months' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        if ($token === '' || strlen($token) > 128) {
            return null;
        }

        return static::where('token_hash', static::hashToken($token))->first();
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at !== null) {
            return self::STATUS_REVOKED;
        }

        if ($this->used_at !== null) {
            return self::STATUS_SIGNED;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_PENDING;
    }

    public function signUrl(string $token): string
    {
        return url('/nda/' . $token);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NdaTemplate::class, 'nda_template_id');
    }

    public function nda(): HasOne
    {
        return $this->hasOne(Nda::class, 'nda_invitation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUsable($query)
    {
        return $query->whereNull('used_at')->whereNull('revoked_at');
    }
}
