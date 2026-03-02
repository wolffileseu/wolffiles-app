<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoScreenshot extends Model
{
    protected $fillable = ['demo_id', 'path', 'thumbnail_path', 'sort_order', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function demo(): BelongsTo { return $this->belongsTo(Demo::class); }
}
