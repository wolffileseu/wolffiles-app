<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestserverLoadedMap extends Model
{
    protected $fillable = [
        'testserver_id',
        'map_slug',
        'file_id',
        'pk3_filenames',
        'total_bytes',
        'loaded_at',
        'use_count',
        'bsp_name',
        'last_used_at',
        'source',
    ];

    protected $casts = [
        'pk3_filenames' => 'array',
        'total_bytes'   => 'integer',
        'use_count'     => 'integer',
        'loaded_at'     => 'datetime',
        'last_used_at'  => 'datetime',
    ];

    public function testserver(): BelongsTo
    {
        return $this->belongsTo(Testserver::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
