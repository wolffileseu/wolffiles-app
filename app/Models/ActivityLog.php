<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'properties', 'ip_address'];
    protected $casts = ['properties' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: filter by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: filter by date range.
     */
    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Get human-readable description.
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user->name ?? 'System';
        $subject = '';

        if ($this->subject) {
            $subject = match (class_basename($this->subject_type)) {
                'File' => $this->subject->title ?? "File #{$this->subject_id}",
                'Comment' => "Comment #{$this->subject_id}",
                'LuaScript' => $this->subject->title ?? "LUA #{$this->subject_id}",
                default => class_basename($this->subject_type) . " #{$this->subject_id}",
            };
        }

        return match ($this->action) {
            'upload' => "{$user} uploaded \"{$subject}\"",
            'download' => "{$user} downloaded \"{$subject}\"",
            'comment' => "{$user} commented on \"{$subject}\"",
            'rate' => "{$user} rated \"{$subject}\" ({$this->properties['rating']}/5)",
            'favorite' => "{$user} favorited \"{$subject}\"",
            'unfavorite' => "{$user} unfavorited \"{$subject}\"",
            'approve' => "{$user} approved \"{$subject}\"",
            'reject' => "{$user} rejected \"{$subject}\"",
            'login' => "{$user} logged in",
            default => "{$user} performed {$this->action}",
        };
    }
}
