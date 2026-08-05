<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nda extends Model
{
    protected $table = 'ndas';

    protected $fillable = [
        'nda_invitation_id',
        'user_id',
        'nda_template_id',
        'template_version',
        'locale',
        'volunteer_name',
        'volunteer_username',
        'volunteer_email',
        'volunteer_discord',
        'volunteer_birthdate',
        'volunteer_country',
        'role_name',
        'role_names',
        'permissions',
        'penalty_amount',
        'log_retention_months',
        'authoritative_language',
        'rendered_body',
        'document_hash',
        'confirmations',
        'signed_at',
        'signed_ip',
        'signed_user_agent',
        'pdf_path',
        'revoked_at',
        'revoked_reason',
    ];

    protected function casts(): array
    {
        return [
            'role_names' => 'array',
            'permissions' => 'array',
            'confirmations' => 'array',
            'penalty_amount' => 'decimal:2',
            'log_retention_months' => 'integer',
            'template_version' => 'integer',
            'volunteer_birthdate' => 'date',
            'signed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashBody(string $body): string
    {
        return hash('sha256', $body);
    }

    /**
     * Prueft, ob der gespeicherte Snapshot seit der Unterschrift
     * unveraendert ist. Grundlage fuer die Beweisfuehrung.
     */
    public function verifyIntegrity(): bool
    {
        return hash_equals(
            (string) $this->document_hash,
            self::hashBody((string) $this->rendered_body)
        );
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(NdaInvitation::class, 'nda_invitation_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NdaTemplate::class, 'nda_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }
}
