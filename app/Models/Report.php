<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'reportable_type', 'reportable_id',
        'reason', 'description', 'status', 'resolved_by', 'resolution_note',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reportable(): MorphTo { return $this->morphTo(); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
