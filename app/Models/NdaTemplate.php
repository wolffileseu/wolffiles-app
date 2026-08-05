<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NdaTemplate extends Model
{
    protected $fillable = [
        'name',
        'locale',
        'version',
        'body',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(NdaInvitation::class);
    }

    public function ndas(): HasMany
    {
        return $this->hasMany(Nda::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function activeFor(string $locale): ?self
    {
        return static::active()
            ->where('locale', $locale)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Ersetzt Platzhalter der Form {{ $key }} bzw. {{$key}}.
     * Bewusst kein Blade-Rendering: der Vertragstext wird nie kompiliert.
     */
    public function render(array $data): string
    {
        return static::renderBody($this->body, $data);
    }

    public static function renderBody(string $body, array $data): string
    {
        $map = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode("\n", array_map(
                    static fn ($item) => '- ' . $item,
                    $value
                ));
            }

            $value = (string) ($value ?? '');

            $map['{{ $' . $key . ' }}'] = $value;
            $map['{{$' . $key . '}}'] = $value;
        }

        return strtr($body, $map);
    }

    /**
     * Liefert alle im Template verwendeten Platzhalternamen.
     */
    public function placeholders(): array
    {
        preg_match_all('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $this->body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
