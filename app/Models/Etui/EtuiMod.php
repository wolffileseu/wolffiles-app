<?php

declare(strict_types=1);

namespace App\Models\Etui;

use App\Etui\Profiles\ModProfile;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class EtuiMod extends Model
{
    use HasFactory;

    protected $table = 'etui_mods';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'parser_profile',
        'homepage_url',
        'icon_path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'order' => 'int',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(EtuiFile::class, 'mod_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(EtuiProject::class, 'mod_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Instantiate the ModProfile subclass named in `parser_profile`. The FQN
     * is trusted because only an admin can write to this column via Filament;
     * a guard against typos still throws rather than fatal-erroring at use.
     */
    protected function profile(): Attribute
    {
        return Attribute::make(
            get: function (): ModProfile {
                $fqn = $this->parser_profile;
                if (! is_string($fqn) || ! class_exists($fqn) || ! is_subclass_of($fqn, ModProfile::class)) {
                    throw new RuntimeException(
                        "EtuiMod#{$this->id} parser_profile points at an unloadable class: '{$fqn}'",
                    );
                }
                return new $fqn();
            },
        );
    }
}
