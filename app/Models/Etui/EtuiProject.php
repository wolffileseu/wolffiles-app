<?php

declare(strict_types=1);

namespace App\Models\Etui;

use App\Etui\Errors\ParseError;
use App\Etui\MenuFile;
use App\Etui\SourceResolver;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EtuiProject extends Model
{
    use HasFactory;

    protected $table = 'etui_projects';

    protected $fillable = [
        'user_id',
        'mod_id',
        'name',
        'content',
        'share_token',
        'is_public',
        'last_saved_at',
        'parse_status',
        'parse_error_count',
        'parsed_at',
    ];

    protected $casts = [
        'is_public' => 'bool',
        'last_saved_at' => 'datetime',
        'parsed_at' => 'datetime',
        'parse_error_count' => 'int',
    ];

    protected static function booted(): void
    {
        // share_token is generated on first save, retried on the (vanishingly
        // unlikely) collision so the column-level unique constraint never
        // throws on creation. 32 random chars over a base62 alphabet is
        // ~190 bits — collision needs ~2^95 projects in the table.
        static::creating(function (EtuiProject $project): void {
            if (! $project->share_token) {
                $project->share_token = static::freshShareToken();
            }
        });
    }

    public static function freshShareToken(): string
    {
        do {
            $candidate = Str::random(32);
        } while (static::where('share_token', $candidate)->exists());
        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mod(): BelongsTo
    {
        return $this->belongsTo(EtuiMod::class, 'mod_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(EtuiRevision::class, 'project_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    protected function shareUrl(): Attribute
    {
        return Attribute::make(
            // Route is registered by Phase 3 — string literal until then so
            // models can still serialise. Once `etui.share` route exists this
            // becomes `route('etui.share', ['token' => $this->share_token])`.
            get: fn (): string => '/etui/share/'.$this->share_token,
        );
    }

    /**
     * Persist the current content as an immutable revision. Phase 3
     * controllers call this BEFORE assigning new content so the previous
     * version is preserved for diff / restore views in Phase 5.
     */
    public function snapshot(): EtuiRevision
    {
        return $this->revisions()->create([
            'content' => (string) $this->content,
        ]);
    }

    public function reparse(?SourceResolver $resolver = null): void
    {
        $result = MenuFile::parse(
            (string) $this->content,
            $this->mod->profile,
            $resolver,
        );

        $hardErrors = array_filter(
            $result->errors->all(),
            static fn (ParseError $e) => $e->severity === ParseError::SEVERITY_ERROR,
        );

        $this->parse_status = $hardErrors === [] ? 'clean' : 'errors';
        $this->parse_error_count = count($hardErrors);
        $this->parsed_at = now();
        $this->save();
    }
}
