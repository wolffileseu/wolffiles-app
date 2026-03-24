<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Clan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "name", "tag", "slug", "description", "website",
        "logo", "contact_discord", "contact_email", "is_active",
    ];

    protected $casts = [
        "is_active" => "boolean",
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($clan) {
            if (empty($clan->slug)) {
                $clan->slug = Str::slug($clan->name);
            }
        });
    }

    public function apiKeys()
    {
        return $this->hasMany(ClanApiKey::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function activeApiKeys()
    {
        return $this->hasMany(ClanApiKey::class)->where("is_active", true);
    }
}
