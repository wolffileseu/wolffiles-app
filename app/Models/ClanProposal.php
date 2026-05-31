<?php
namespace App\Models;

use App\Models\Tracker\TrackerClan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClanProposal extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_MERGED   = 'merged';

    protected $fillable = [
        'user_id', 'tag', 'tag_clean', 'name', 'description', 'website', 'discord',
        'status', 'reviewed_by', 'reviewed_at', 'review_note', 'created_tracker_clan_id',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function createdTrackerClan(): BelongsTo { return $this->belongsTo(TrackerClan::class, 'created_tracker_clan_id'); }

    public function scopePending($q) { return $q->where('status', self::STATUS_PENDING); }

    /**
     * Check whether a tracker_clan already matches this proposal.
     * Returns the existing TrackerClan or null.
     */
    public function findExistingTrackerClan(): ?TrackerClan
    {
        return TrackerClan::where('tag_clean', $this->tag_clean)->first();
    }

    /**
     * Approve this proposal. Creates a new tracker_clan (or links to an existing
     * one with same tag_clean), then runs linkRegisteredClan() to create the
     * registered Clan + make the proposer the owner.
     */
    public function approve(int $reviewerId, ?string $note = null): void
    {
        $existing = $this->findExistingTrackerClan();

        if ($existing) {
            $trackerClan = $existing;
            $finalStatus = self::STATUS_MERGED;
            $note = ($note ? $note . " " : "") . "Merged into existing tracker_clan #{$existing->id}";
        } else {
            $trackerClan = TrackerClan::create([
                'tag'           => $this->tag,
                'tag_clean'     => $this->tag_clean,
                'name'          => $this->name,
                'description'   => $this->description,
                'website'       => $this->website,
                'discord'       => $this->discord,
                'status'        => 'active',
                'is_verified'   => true,
                'is_locked'     => true,
                'first_seen_at' => now(),
                'last_seen_at'  => now(),
                'member_count'  => 0,
                'active_member_count' => 0,
                'avg_elo'       => 1000.00,
                'total_play_time_minutes' => 0,
            ]);
            $finalStatus = self::STATUS_APPROVED;
        }

        // Reuse linkRegisteredClan via a synthetic TrackerClaim
        $claim = new \App\Models\Tracker\TrackerClaim([
            'user_id'        => $this->user_id,
            'claimable_type' => 'clan',
            'claimable_id'   => $trackerClan->id,
            'status'         => 'approved',
        ]);
        $claim->user_id = $this->user_id; // ensure for protected method
        $ref = new \ReflectionMethod(\App\Models\Tracker\TrackerClaim::class, 'linkRegisteredClan');
        $ref->setAccessible(true);
        $ref->invoke($claim, $trackerClan);

        $this->update([
            'status'                 => $finalStatus,
            'reviewed_by'            => $reviewerId,
            'reviewed_at'            => now(),
            'review_note'            => $note,
            'created_tracker_clan_id'=> $trackerClan->id,
        ]);
    }

    public function reject(int $reviewerId, ?string $note = null): void
    {
        $this->update([
            'status'      => self::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }
}
