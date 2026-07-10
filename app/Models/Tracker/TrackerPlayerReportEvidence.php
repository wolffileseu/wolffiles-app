<?php

namespace App\Models\Tracker;

use Throwable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TrackerPlayerReportEvidence extends Model
{
    protected $table = 'tracker_player_report_evidence';
    public $timestamps = false;

    protected $fillable = ['report_id', 'file_path', 'caption', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }

    public function report(): BelongsTo { return $this->belongsTo(TrackerPlayerReport::class, 'report_id'); }

    public function url(int $minutes = 60): ?string
    {
        if (!$this->file_path) return null;
        try { return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addMinutes($minutes)); }
        catch (Throwable $e) { return null; }
    }
}
