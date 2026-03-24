<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    const TYPE_NEWS        = "news";
    const TYPE_EVENT       = "event";
    const TYPE_MATCH       = "match";
    const TYPE_RECRUITMENT = "recruitment";

    const TYPES = [
        self::TYPE_NEWS        => "News / Ankündigung",
        self::TYPE_EVENT       => "Event",
        self::TYPE_MATCH       => "Match / Ergebnis",
        self::TYPE_RECRUITMENT => "Rekrutierung",
    ];

    protected $table = "posts";

    protected $fillable = [
        "user_id", "clan_id", "type",
        "title", "slug", "title_translations", "excerpt",
        "content", "content_translations", "featured_image",
        "is_published", "is_pinned", "published_at", "view_count",
        "event_date", "event_location",
        "match_opponent", "match_result", "match_map",
        "recruitment_requirements",
    ];

    protected function casts(): array
    {
        return [
            "is_published"             => "boolean",
            "is_pinned"                => "boolean",
            "published_at"             => "datetime",
            "event_date"               => "datetime",
            "title_translations"       => "array",
            "content_translations"     => "array",
            "recruitment_requirements" => "array",
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom("title")->saveSlugsTo("slug");
    }

    public function getRouteKeyName(): string { return "slug"; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function clan(): BelongsTo { return $this->belongsTo(Clan::class); }
    public function comments(): MorphMany { return $this->morphMany(Comment::class, "commentable"); }
    public function tags() { return $this->morphToMany(Tag::class, "taggable"); }

    public function scopePublished($query)
    {
        return $query->where("is_published", true)
            ->where("published_at", "<=", now());
    }

    public function scopePinned($query) { return $query->where("is_pinned", true); }
    public function scopeOfType($query, string $type) { return $query->where("type", $type); }
    public function scopeClanPosts($query) { return $query->whereNotNull("clan_id"); }
    public function scopeSitePosts($query) { return $query->whereNull("clan_id"); }

    public function isClanPost(): bool { return $this->clan_id !== null; }
}
