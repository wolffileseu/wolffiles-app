<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $path
 * @property string $status
 * @property \Carbon\Carbon|null $published_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\User|null $user
 */
class Demo extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'user_id', 'category_id', 'reviewed_by', 'title', 'slug',
        'description', 'description_html',
        'file_path', 'file_name', 'file_extension', 'file_size',
        'file_hash', 'mime_type',
        'game', 'map_name', 'mod_name', 'gametype', 'match_format',
        'duration_seconds', 'demo_format',
        'team_axis', 'team_allies', 'match_date', 'match_source',
        'match_source_url', 'recorder_name', 'server_name',
        'player_list',
        'status', 'rejection_reason', 'reviewed_at', 'published_at',
        'download_count', 'view_count', 'average_rating', 'rating_count',
        'is_featured', 'featured_label',
        'virus_scanned', 'virus_clean', 'virus_scan_result',
    ];

    protected function casts(): array
    {
        return [
            'player_list' => 'array',
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'download_count' => 'integer',
            'view_count' => 'integer',
            'average_rating' => 'decimal:2',
            'rating_count' => 'integer',
            'is_featured' => 'boolean',
            'virus_scanned' => 'boolean',
            'virus_clean' => 'boolean',
            'match_date' => 'date',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function screenshots(): HasMany { return $this->hasMany(DemoScreenshot::class)->orderBy('sort_order'); }
    public function primaryScreenshot() { return $this->hasOne(DemoScreenshot::class)->where('is_primary', true); }
    public function comments(): MorphMany { return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id'); }
    public function allComments(): MorphMany { return $this->morphMany(Comment::class, 'commentable'); }
    public function tags(): \Illuminate\Database\Eloquent\Relations\MorphToMany { return $this->morphToMany(Tag::class, 'taggable'); }
    public function reports(): MorphMany { return $this->morphMany(Report::class, 'reportable'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeFeatured($query) { return $query->where('is_featured', true); }
    public function scopeForGame($query, string $game) { return $query->where('game', $game); }
    public function scopePopular($query) { return $query->orderByDesc('download_count'); }
    public function scopeRecent($query) { return $query->orderByDesc('published_at'); }

    public function scopeSearch($query, string $term)
    {
        $term = trim($term);
        if (empty($term)) return $query;
        $like = '%' . $term . '%';
        return $query->where(function ($q) use ($like) {
            $q->where('title', 'LIKE', $like)
              ->orWhere('description', 'LIKE', $like)
              ->orWhere('map_name', 'LIKE', $like)
              ->orWhere('team_axis', 'LIKE', $like)
              ->orWhere('team_allies', 'LIKE', $like)
              ->orWhere('recorder_name', 'LIKE', $like)
              ->orWhere('server_name', 'LIKE', $like);
        });
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_seconds) return null;
        $hours = floor($this->duration_seconds / 3600);
        $mins = floor(($this->duration_seconds % 3600) / 60);
        $secs = $this->duration_seconds % 60;
        if ($hours > 0) return sprintf('%d:%02d:%02d', $hours, $mins, $secs);
        return sprintf('%d:%02d', $mins, $secs);
    }

    public function getMatchTitleAttribute(): string
    {
        if ($this->team_axis && $this->team_allies) {
            return $this->team_axis . ' vs ' . $this->team_allies;
        }
        return $this->title;
    }

    public function getDownloadUrlAttribute(): string { return route('demos.download', $this); }

    public function getGameLabelAttribute(): string
    {
        return match ($this->game) {
            'ET' => 'Enemy Territory',
            'RtCW' => 'Return to Castle Wolfenstein',
            'Q3' => 'Quake 3 Arena',
            'ETQW' => 'ET: Quake Wars',
            default => $this->game ?? 'Unknown',
        };
    }

    public function getFormatBadgeAttribute(): string
    {
        return match ($this->demo_format) {
            'dm_84' => 'ET 2.60b',
            'dm_83' => 'ET 2.56',
            'dm_82' => 'ET 2.55',
            'dm_60' => 'RtCW',
            'tv_84' => 'ETTV',
            default => $this->demo_format ?? 'Unknown',
        };
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $screenshot = $this->primaryScreenshot ?? $this->screenshots->first();
        if (!$screenshot) return null;
        return \Storage::disk('s3')->temporaryUrl($screenshot->path, now()->addHours(2));
    }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
    public function incrementDownloads(): void { $this->increment('download_count'); }
    public function incrementViews(): void { $this->increment('view_count'); }
}
