<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TrackerBanEvidence extends Model
{
    protected $table = 'tracker_ban_evidence';

    public $timestamps = false; // only created_at, set manually

    protected $fillable = [
        'ban_id', 'type', 'file_path', 'external_url',
        'caption', 'is_public', 'server_id', 'occurred_at',
        'uploaded_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_public'   => 'boolean',
            'occurred_at' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public function ban(): BelongsTo { return $this->belongsTo(TrackerBan::class, 'ban_id'); }
    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'uploaded_by'); }

    /**
     * Signed, expiring S3 URL for the evidence file (screenshots/demos are
     * stored private). Returns external_url directly for link/video types.
     */
    public function url(int $minutes = 60): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }
        if ($this->file_path) {
            return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addMinutes($minutes));
        }
        return null;
    }
}
