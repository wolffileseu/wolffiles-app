<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon|null $email_verified_at
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection $badges
 */
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'discord_id',
        'discord_username', 'telegram_username', 'clan', 'favorite_games', 'bio', 'website', 'locale',
        'is_active', 'last_login_at', 'notification_preferences', 'profile_data',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'favorite_games' => 'array',
            'notification_preferences' => 'array',
            'profile_data' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole(['admin', 'moderator']);
        }

        return true;
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function luaScripts(): HasMany
    {
        return $this->hasMany(LuaScript::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withPivot('earned_at')->withTimestamps();
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function approvedFiles(): HasMany
    {
        return $this->files()->where('status', 'approved');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return \Storage::disk('s3')->url($this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random';
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isModerator(): bool
    {
        return $this->hasAnyRole(['admin', 'moderator']);
    }
}
