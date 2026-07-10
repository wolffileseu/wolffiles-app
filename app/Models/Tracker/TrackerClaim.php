<?php

namespace App\Models\Tracker;

use App\Models\Clan;
use Illuminate\Support\Str;
use App\Models\ClanManager;
use Carbon\Carbon;
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
 * @property Carbon|null $reviewed_at
 * @property Model|null $entity
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
                $updates['is_locked'] = true; // stop auto-overwrite by ClanDetectionService
                $clan->update($updates);
                $this->linkRegisteredClan($clan);
            }
        } elseif ($this->claimable_type === 'server') {
            $server = TrackerServer::find($this->claimable_id);
            if ($server) {
                $updates = [
                    'claimed_by_user_id' => $this->user_id,
                    'is_verified' => true,
                ];
                // Note: clan linking is now manual via /servers/{id}/manage (not auto on claim).
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
     * Create or link a registered Clan (clans table) for a claimed tracker clan,
     * and make the claiming user the owner. Idempotent.
     */
    protected function linkRegisteredClan(TrackerClan $trackerClan): void
    {
        $registered = Clan::firstOrNew(['tracker_clan_id' => $trackerClan->id]);
        $registered->fill([
            'tracker_clan_id' => $trackerClan->id,
            'name'            => $registered->name ?: ($trackerClan->name ?: $trackerClan->tag_clean),
            'tag'             => $registered->tag ?: $trackerClan->tag_clean,
            'description'     => $registered->description ?: $trackerClan->description,
            'website'         => $registered->website ?: $trackerClan->website,
            'contact_discord' => $registered->contact_discord ?: $trackerClan->discord,
            'is_active'       => true,
            'is_published'    => $registered->exists ? $registered->is_published : false,
        ]);
        if (empty($registered->slug)) {
            $base = Str::slug($registered->name) ?: 'clan';
            $slug = $base; $i = 2;
            while (Clan::where('slug', $slug)->where('id', '!=', $registered->id ?? 0)->exists()) { $slug = $base.'-'.$i; $i++; }
            $registered->slug = $slug;
        }
        $registered->save();

        ClanManager::firstOrCreate(
            ['clan_id' => $registered->id, 'user_id' => $this->user_id],
            ['role' => ClanManager::ROLE_OWNER]
        );
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
