<?php

declare(strict_types=1);

namespace App\Models\Etui;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtuiRevision extends Model
{
    use HasFactory;

    protected $table = 'etui_revisions';

    // A revision is immutable — the table has no updated_at column.
    public const UPDATED_AT = null;

    protected $fillable = ['project_id', 'content'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(EtuiProject::class, 'project_id');
    }

    public function scopeRecent($query, int $n = 10)
    {
        return $query->orderByDesc('created_at')->limit($n);
    }
}
