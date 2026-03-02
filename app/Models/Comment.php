<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'commentable_type', 'commentable_id',
        'parent_id', 'body', 'is_approved',
    ];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function commentable(): MorphTo { return $this->morphTo(); }
    public function parent(): BelongsTo { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies(): HasMany { return $this->hasMany(Comment::class, 'parent_id'); }
}
