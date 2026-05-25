<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotwHistory extends Model
{
    protected $table = 'motw_history';
    public $timestamps = false;

    protected $fillable = ['file_id', 'featured_at', 'strategy', 'game'];
    protected $casts = ['featured_at' => 'datetime'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public static function recentFileIds(int $weeks = 12): array
    {
        return self::orderByDesc('featured_at')
            ->limit($weeks)
            ->pluck('file_id')
            ->toArray();
    }

    public static function record(File $file, ?string $strategy, ?string $game): void
    {
        self::create([
            'file_id'     => $file->id,
            'featured_at' => now(),
            'strategy'    => $strategy,
            'game'        => $game,
        ]);
    }
}
