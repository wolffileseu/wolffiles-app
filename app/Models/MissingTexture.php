<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissingTexture extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'texture_path',
        'game',
        'request_count',
        'first_seen_at',
        'last_seen_at',
        'resolved',
        'notes',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'resolved'      => 'boolean',
        'request_count' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
