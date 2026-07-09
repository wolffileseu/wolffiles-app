<?php

namespace App\Models\Tracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackerPlayerReport extends Model
{
    protected $table = 'tracker_player_reports';

    protected $fillable = [
        'user_id', 'reported_player_id', 'reported_guid', 'description',
        'contact', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
        'resulting_ban_id',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'reported_player_id'); }
    public function resultingBan(): BelongsTo { return $this->belongsTo(TrackerBan::class, 'resulting_ban_id'); }
    public function evidence(): HasMany { return $this->hasMany(TrackerPlayerReportEvidence::class, 'report_id'); }
}
