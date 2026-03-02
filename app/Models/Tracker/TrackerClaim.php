<?php

namespace App\Models\Tracker;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $claimable_type
 * @property int $claimable_id
 * @property string $status
 * @property string|null $message
 * @property \Carbon\Carbon|null $reviewed_at
 * @property \Illuminate\Database\Eloquent\Model|null $entity
 */
class TrackerClaim extends Model
{
    protected $table = 'tracker_claims';

    protected $fillable = [
        'user_id', 'claimable_type', 'claimable_id', 'status',
        'message', 'proof_type',
        'clan_email', 'clan_website', 'clan_discord', 'clan_description',
        'reviewed_by', 'review_note', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function claimable()
    {
        return $this->morphTo('claimable', 'claimable_type', 'claimable_id');
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ──

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPlayerClaim(): bool { return $this->claimable_type === 'player'; }
    public function isClanClaim(): bool { return $this->claimable_type === 'clan'; }
    public function isServerClaim(): bool { return $this->claimable_type === 'server'; }

    /**
     * Approve this claim and update the linked entity
     */
    public function approve(int $reviewerId, ?string $note = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        if ($this->claimable_type === 'player') {
            $player = TrackerPlayer::find($this->claimable_id);
            if ($player) {
                $player->update([
                    'claimed_by_user_id' => $this->user_id,
                    'is_verified' => true,
                ]);
            }
        } elseif ($this->claimable_type === 'clan') {
            $clan = TrackerClan::find($this->claimable_id);
            if ($clan) {
                $updates = [
                    'claimed_by_user_id' => $this->user_id,
                    'is_verified' => true,
                ];
                if ($this->clan_email) $updates['clan_email'] = $this->clan_email;
                if ($this->clan_website) $updates['website'] = $this->clan_website;
                if ($this->clan_discord) $updates['discord'] = $this->clan_discord;
                if ($this->clan_description) $updates['description'] = $this->clan_description;
                $clan->update($updates);
            }
        } elseif ($this->claimable_type === 'server') {
            $server = TrackerServer::find($this->claimable_id);
            if ($server) {
                $updates = [
                    'claimed_by_user_id' => $this->user_id,
                    'is_verified' => true,
                ];
                // Server details are stored in clan_* fields (reused)
                if ($this->clan_description) $updates['description'] = $this->clan_description;
                if ($this->clan_website) $updates['server_website'] = $this->clan_website;
                if ($this->clan_discord) $updates['server_discord'] = $this->clan_discord;
                if ($this->clan_email) $updates['server_email'] = $this->clan_email;
                $server->update($updates);
            }
        }

        // Reject other pending claims for the same entity
        static::where('claimable_type', $this->claimable_type)
            ->where('claimable_id', $this->claimable_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewerId,
                'review_note' => 'Auto-rejected: another claim was approved.',
                'reviewed_at' => now(),
            ]);
    }

    /**
     * Reject this claim
     */
    public function reject(int $reviewerId, ?string $note = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);
    }
}
