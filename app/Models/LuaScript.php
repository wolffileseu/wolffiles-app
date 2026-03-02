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

class LuaScript extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'user_id', 'category_id', 'reviewed_by', 'title', 'slug',
        'description', 'description_html', 'installation_guide',
        'file_path', 'file_name', 'file_size', 'file_hash', 'version',
        'compatible_mods', 'dependencies', 'min_lua_version',
        'status', 'rejection_reason', 'reviewed_at', 'published_at',
        'download_count', 'view_count', 'average_rating', 'rating_count',
        'virus_scanned', 'virus_clean', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'compatible_mods' => 'array',
            'dependencies' => 'array',
            'is_featured' => 'boolean',
            'virus_scanned' => 'boolean',
            'virus_clean' => 'boolean',
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
    public function screenshots(): HasMany { return $this->hasMany(LuaScriptScreenshot::class)->orderBy('sort_order'); }
    public function comments(): MorphMany { return $this->morphMany(Comment::class, 'commentable'); }
    public function tags() { return $this->morphToMany(Tag::class, 'taggable'); }
    public function reports(): MorphMany { return $this->morphMany(Report::class, 'reportable'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
