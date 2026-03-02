<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TutorialStep extends Model
{
    protected $fillable = [
        'tutorial_id', 'step_number', 'title', 'content', 'image_path', 'video_url', 'tip',
    ];

    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        if (str_starts_with($this->image_path, 'http')) return $this->image_path;
        return Storage::disk('s3')->url($this->image_path);
    }
}
