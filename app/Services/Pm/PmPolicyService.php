<?php

namespace App\Services\Pm;

use App\Exceptions\Pm\PmServiceException;
use App\Models\Pm\PmConversation;
use App\Models\Pm\PmUserBlock;
use App\Models\Pm\PmUserSetting;
use App\Models\User;

/**
 * Decides "may $sender do $action involving $target?".
 * Returns ["ok" => bool, "reason" => ?string].
 *
 * Reason codes (used as i18n keys in UI):
 *   - "self_message"               -- sender == recipient
 *   - "blocked_by_recipient"       -- recipient has blocked sender
 *   - "recipient_privacy_nobody"   -- recipient set who_can_message=nobody
 *   - "conversation_locked"        -- mod/admin locked the conversation
 *   - "not_a_participant"          -- sender not in conversation
 *
 * Admins bypass blocks and privacy settings (system-level support contact).
 * Moderators do NOT bypass; they message via the normal mechanism.
 */
class PmPolicyService
{
    /**
     * Can $sender start or continue a 1:1 conversation with $recipient?
     */
    public function canSendTo(User $sender, User $recipient): array
    {
        if ($sender->id === $recipient->id) {
            return ["ok" => false, "reason" => "self_message"];
        }

        // Admins bypass user-level restrictions, but never bypass
        // self-message check above.
        if ($sender->hasRole("admin")) {
            return ["ok" => true, "reason" => null];
        }

        // Did the recipient block the sender?
        if (PmUserBlock::where("blocker_id", $recipient->id)
                ->where("blocked_id", $sender->id)
                ->exists()) {
            return ["ok" => false, "reason" => "blocked_by_recipient"];
        }

        // Recipient privacy: only check if settings exist; absence = default everyone
        $settings = PmUserSetting::find($recipient->id);
        if ($settings && ! $settings->allowsAnyMessage()) {
            return ["ok" => false, "reason" => "recipient_privacy_nobody"];
        }

        return ["ok" => true, "reason" => null];
    }

    /**
     * Can $sender post into an existing conversation?
     * Used for both direct (after canSendTo passed) and group conversations.
     */
    public function canSendToConversation(User $sender, PmConversation $conv): array
    {
        if ($conv->locked) {
            // Admins can still post into locked conversations (e.g. to add a notice)
            if (! $sender->hasRole("admin")) {
                return ["ok" => false, "reason" => "conversation_locked"];
            }
        }

        if (! $conv->hasParticipant($sender->id)) {
            return ["ok" => false, "reason" => "not_a_participant"];
        }

        return ["ok" => true, "reason" => null];
    }

    // -----------------------------------------------------------------------
    // Block management
    // -----------------------------------------------------------------------

    /**
     * Block another user. Idempotent: returns existing block if present.
     */
    public function block(User $blocker, User $blocked, ?string $reason = null): PmUserBlock
    {
        if ($blocker->id === $blocked->id) {
            throw new PmServiceException(
                "self_block",
                "Cannot block yourself."
            );
        }

        return PmUserBlock::firstOrCreate(
            [
                "blocker_id" => $blocker->id,
                "blocked_id" => $blocked->id,
            ],
            [
                "reason" => $reason ? mb_substr($reason, 0, 200) : null,
            ]
        );
    }

    /**
     * Unblock. Idempotent: silent if no block exists.
     */
    public function unblock(User $blocker, User $blocked): void
    {
        PmUserBlock::where("blocker_id", $blocker->id)
            ->where("blocked_id", $blocked->id)
            ->delete();
    }

    /**
     * Check if $blocker has blocked $blocked.
     */
    public function hasBlocked(User $blocker, User $blocked): bool
    {
        return PmUserBlock::where("blocker_id", $blocker->id)
            ->where("blocked_id", $blocked->id)
            ->exists();
    }

    // -----------------------------------------------------------------------
    // Settings convenience
    // -----------------------------------------------------------------------

    public function getOrCreateSettings(User $user): PmUserSetting
    {
        return PmUserSetting::forUser($user->id);
    }
}
