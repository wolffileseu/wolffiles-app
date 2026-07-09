<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Models\Tracker\TrackerMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property \Carbon\Carbon|null $published_at
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $path
 * @property string|null $thumbnail_path
 * @property string|null $status
 * @property string|null $original_author
 * @property string|null $filename
 */
/**
 * @property-read Collection|Tag[] $tags
 * @property-read Category|null $category
 * @property-read User|null $user
 * @property-read Collection|FileScreenshot[] $screenshots
 */
class File extends Model
{
    use HasFactory, HasSlug;

    protected $table = 'files';

    protected $fillable = [
        'user_id', 'category_id', 'reviewed_by', 'title', 'slug',
        'description', 'description_html', 'title_translations',
        'description_translations', 'file_path', 'file_name',
        'file_extension', 'file_size', 'file_hash', 'mime_type',
        'map_name', 'game', 'mod_compatibility', 'version',
        'original_author', 'readme_content', 'extracted_metadata',
        'status', 'rejection_reason', 'reviewed_at', 'published_at',
        'download_count', 'view_count', 'average_rating', 'rating_count',
        'is_featured', 'featured_at', 'featured_label',
        'virus_scanned', 'virus_clean', 'virus_scan_result',
        'bsp_path', 'fastdownload_path', 'fastdownload_available',
        'playable_path', 'playable_mime', 'playable_size',
        'playable_duration_seconds', 'playable_codec',
        'playable_status', 'playable_error', 'playable_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'description_translations' => 'array',
            'extracted_metadata' => 'array',
            'mod_compatibility' => 'array',
            'fastdownload_available' => 'boolean',
            'file_size' => 'integer',
            'download_count' => 'integer',
            'view_count' => 'integer',
            'average_rating' => 'decimal:2',
            'rating_count' => 'integer',
            'is_featured' => 'boolean',
            'virus_scanned' => 'boolean',
            'virus_clean' => 'boolean',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'featured_at' => 'datetime',
            'playable_processed_at' => 'datetime',
            'playable_size' => 'integer',
            'playable_duration_seconds' => 'integer',
        ];
    }

    public function getFilenameAttribute(): ?string
    {
        return $this->attributes['file_name'] ?? null;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Relationships
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function screenshots(): HasMany { return $this->hasMany(FileScreenshot::class)->orderBy('sort_order'); }
    public function primaryScreenshot() { return $this->hasOne(FileScreenshot::class)->where('is_primary', true); }
    public function versions(): HasMany { return $this->hasMany(FileVersion::class)->orderByDesc('created_at'); }
    public function ratings(): HasMany { return $this->hasMany(Rating::class); }
    public function comments(): MorphMany { return $this->morphMany(Comment::class, 'commentable')->whereNull('parent_id'); }
    public function allComments(): MorphMany { return $this->morphMany(Comment::class, 'commentable'); }
    public function tags(): MorphToMany { return $this->morphToMany(Tag::class, 'taggable'); }
    public function downloads(): HasMany { return $this->hasMany(Download::class); }
    public function downloadLogs(): HasMany { return $this->hasMany(Download::class); }
    public function favorites(): HasMany { return $this->hasMany(Favorite::class); }
    public function trackerMaps(): HasMany { return $this->hasMany(TrackerMap::class); }
    public function reports(): MorphMany { return $this->morphMany(Report::class, 'reportable'); }

    // Scopes
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeFeatured($query) { return $query->where('is_featured', true); }
    public function scopeForGame($query, string $game) { return $query->where('game', $game); }
    public function scopePopular($query) { return $query->orderByDesc('download_count'); }
    public function scopeRecent($query) { return $query->orderByDesc('published_at'); }

    /**
     * Search scope - uses LIKE for reliable results, with FULLTEXT boost
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);
        if (empty($term)) return $query;

        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('title', 'LIKE', $like)
              ->orWhere('description', 'LIKE', $like)
              ->orWhere('map_name', 'LIKE', $like)
              ->orWhere('original_author', 'LIKE', $like)
              ->orWhere('file_name', 'LIKE', $like)
              ->orWhere('readme_content', 'LIKE', $like);
        });
    }

    // Helpers
    public function getDisplayTitleAttribute(): string
    {
        if ($this->version) {
            return $this->title . ' - v' . $this->version;
        }
        return $this->title;
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function getDownloadUrlAttribute(): string { return route('files.download', $this); }

    /**
     * Is this file a playable video that's been processed and ready to stream?
     */
    public function isPlayableVideo(): bool
    {
        return $this->playable_status === 'ready'
            && !empty($this->playable_path);
    }

    /**
     * Does this file (or its archive contents) contain a video?
     */
    public function hasVideoContent(): bool
    {
        $videoExts = ['mkv', 'mp4', 'avi', 'wmv', 'mpg', 'mpeg', 'flv', 'mov', 'webm', 'm4v'];

        if (in_array(strtolower($this->file_extension ?? ''), $videoExts, true)) {
            return true;
        }

        $fileTypes = $this->extracted_metadata['file_types'] ?? [];
        foreach ($videoExts as $ext) {
            if (!empty($fileTypes[$ext])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Streamable signed URL for the playable MP4 (inline disposition, 6h TTL).
     */
    public function getPlayableUrlAttribute(): ?string
    {
        if (!$this->isPlayableVideo()) {
            return null;
        }
        return Storage::disk('s3')->temporaryUrl(
            $this->playable_path,
            now()->addHours(6),
            [
                'ResponseContentDisposition' => 'inline',
                'ResponseContentType' => $this->playable_mime ?? 'video/mp4',
            ]
        );
    }


    public function getPrimaryImageUrlAttribute(): ?string
    {
        $screenshot = $this->primaryScreenshot ?? $this->screenshots->first();
        if (!$screenshot) return null;
        return \Storage::disk('s3')->url($screenshot->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $screenshot = $this->primaryScreenshot ?? $this->screenshots->first();
        if (!$screenshot) return null;
        $path = $screenshot->thumbnail_path ?? $screenshot->path;
        return \Storage::disk('s3')->url($path);
    }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function incrementDownloads(): void { $this->increment('download_count'); }
    public function incrementViews(): void { $this->increment('view_count'); }

    public function recalculateRating(): void
    {
        $this->update([
            'average_rating' => $this->ratings()->avg('rating') ?? 0,
            'rating_count' => $this->ratings()->count(),
        ]);
    }

    /**
     * Bot/Waypoint files related to this map (this = map).
     */
    public function relatedBots()
    {
        return $this->belongsToMany(
            File::class,
            'file_map_relations',
            'map_file_id',
            'bot_file_id'
        )
            ->withPivot(['relation_type', 'confidence', 'source', 'is_manual'])
            ->withTimestamps()
            ->orderByPivot('confidence', 'desc');
    }

    /**
     * Map files this bot/waypoint file belongs to (this = bot).
     */
    public function relatedMaps()
    {
        return $this->belongsToMany(
            File::class,
            'file_map_relations',
            'bot_file_id',
            'map_file_id'
        )
            ->withPivot(['relation_type', 'confidence', 'source', 'is_manual'])
            ->withTimestamps()
            ->orderByPivot('confidence', 'desc');
    }

    public function isMap(): bool
    {
        return (int) $this->category_id === 10;
    }

    public function isBotFile(): bool
    {
        return (int) $this->category_id === 12;
    }
}