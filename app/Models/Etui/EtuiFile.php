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
use Illuminate\Support\Number;

class EtuiFile extends Model
{
    use HasFactory;

    protected $table = 'etui_files';

    protected $fillable = [
        'mod_id',
        'name',
        'content',
        'source',
        'uploader_id',
        'is_public',
        'hash',
        'parse_status',
        'parse_error_count',
        'parsed_at',
    ];

    protected $casts = [
        'is_public' => 'bool',
        'parse_error_count' => 'int',
        'parsed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Keep `hash` in sync with `content` automatically — the unique
        // (mod_id, name, hash) constraint relies on this, and Filament forms
        // would otherwise have to remember to recompute it on every save.
        static::saving(function (EtuiFile $file): void {
            if ($file->isDirty('content')) {
                $file->hash = hash('sha256', (string) $file->content);
            }
        });
    }

    public function mod(): BelongsTo
    {
        return $this->belongsTo(EtuiMod::class, 'mod_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function scopeStock($query)
    {
        return $query->where('source', 'stock');
    }

    public function scopeUser($query)
    {
        return $query->where('source', 'user');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    protected function humanFilesize(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Number::fileSize(strlen((string) $this->content)),
        );
    }

    /**
     * Re-parse the file's content through the full pipeline and update the
     * three parse_* columns. Phase 3 will call this from a controller after
     * uploads; Phase 2 calls it from the Filament row action.
     */
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
