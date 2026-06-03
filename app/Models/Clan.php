<?php
namespace App\Models;

use App\Models\Tracker\TrackerClan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Clan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "name", "tag", "slug", "description", "website",
        "logo", "banner", "contact_discord", "contact_email", "ts_address",
        "rules", "location", "founded", "is_active",
        "tracker_clan_id", "tag_display", "is_published", "is_recruiting", "recruitment_summary", "view_count",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "is_published" => "boolean",
        "is_recruiting" => "boolean",
        "view_count" => "integer",
        'slug_changed_at' => 'datetime',
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

    public function getRouteKeyName(): string { return "slug"; }

    // --- bestehende Relations ---
    public function apiKeys(): HasMany { return $this->hasMany(ClanApiKey::class); }
    public function activeApiKeys(): HasMany { return $this->hasMany(ClanApiKey::class)->where("is_active", true); }
    public function posts(): HasMany { return $this->hasMany(Post::class); }

    // --- NEU: Verknüpfungen ---
    public function trackerClan(): BelongsTo { return $this->belongsTo(TrackerClan::class, "tracker_clan_id"); }
    public function managers(): HasMany { return $this->hasMany(ClanManager::class); }
    /** Members of this clan, via the linked tracker_clan. */
    public function members(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Tracker\TrackerClanMember::class,
            \App\Models\Tracker\TrackerClan::class,
            'id',              // tracker_clans.id (Foreign on this)
            'clan_id',         // tracker_clan_members.clan_id (Foreign on through)
            'tracker_clan_id', // clans.tracker_clan_id (Local)
            'id'               // tracker_clans.id (Local on through)
        );
    }
    public function applications(): HasMany { return $this->hasMany(ClanApplication::class); }

    public function news(): HasMany {
        return $this->hasMany(Post::class)->where("type", Post::TYPE_NEWS)->where("is_published", true)->latest("published_at");
    }
    public function recruitmentPosts(): HasMany {
        return $this->hasMany(Post::class)->where("type", Post::TYPE_RECRUITMENT)->where("is_published", true)->latest("published_at");
    }

    // --- Rechte-Helfer ---
    public function managerFor(?User $user): ?ClanManager {
        if (!$user) return null;
        return $this->managers->firstWhere("user_id", $user->id);
    }
    public function isManagedBy(?User $user): bool { return (bool) $this->managerFor($user); }
    public function isOwnedBy(?User $user): bool {
        $m = $this->managerFor($user);
        return $m && $m->role === ClanManager::ROLE_OWNER;
    }

    // --- Accessors ---
    public function getDisplayTagAttribute(): string {
        return $this->tag_display ?: ("[" . $this->tag . "]");
    }

    /** Returns the server hostname prefix pattern this clan auto-matches (e.g. "[RoG]" or "=RoG="). */
    public function getServerMatchPrefixAttribute(): string {
        return $this->tag_display ?: ("[" . $this->tag . "]");
    }

    /** Query auto-detected unclaimed servers whose hostname starts with this clan's match prefix. */
    public function autoDetectedServersQuery() {
        $prefix = $this->server_match_prefix;
        $like = addcslashes($prefix, '%_\\') . '%';
        return \App\Models\Tracker\TrackerServer::query()
            ->whereNull('claimed_by_clan_id')
            ->where(function($q) use ($like) {
                $q->where('hostname_clean', 'LIKE', $like)
                  ->orWhere('hostname', 'LIKE', $like);
            });
    }

    // --- Scopes ---
    public function scopePublished($q) { return $q->where("is_published", true)->where("is_active", true); }
    public function scopeRecruiting($q) { return $q->where("is_recruiting", true); }
}
