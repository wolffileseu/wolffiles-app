<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClanApplication extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = ['clan_id','applicant_user_id','player_name','contact','message','status','reviewed_by_user_id','reviewed_at'];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function clan(): BelongsTo { return $this->belongsTo(Clan::class); }
    public function applicant(): BelongsTo { return $this->belongsTo(User::class, 'applicant_user_id'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by_user_id'); }

    public function scopePending($q) { return $q->where('status', self::STATUS_PENDING); }
}
