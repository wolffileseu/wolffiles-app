<?php

namespace App\Services\Pm;

use App\Models\Pm\PmConversation;
use App\Models\User;

/**
 * Decides "may $sender do $action involving $target?".
 * Returns ["ok" => bool, "reason" => ?string].
 *
 * Reasons (string codes for i18n in UI):
 *   - "blocked_by_recipient"
 *   - "recipient_privacy_nobody"
 *   - "recipient_privacy_clan_only"
 *   - "self_message"
 *   - "conversation_locked"
 *   - "not_a_participant"
 *
 * Phase 3d.1: STUB. Returns ["ok"=>true] for everything.
 * Phase 3d.2: real implementation against PmUserBlock + PmUserSetting.
 */
class PmPolicyService
{
    public function canSendTo(User $sender, User $recipient): array
    {
        // STUB: real checks come in Phase 3d.2
        if ($sender->id === $recipient->id) {
            return ["ok" => false, "reason" => "self_message"];
        }
        return ["ok" => true, "reason" => null];
    }

    public function canSendToConversation(User $sender, PmConversation $conv): array
    {
        if ($conv->locked) {
            return ["ok" => false, "reason" => "conversation_locked"];
        }
        if (! $conv->hasParticipant($sender->id)) {
            return ["ok" => false, "reason" => "not_a_participant"];
        }
        return ["ok" => true, "reason" => null];
    }
}
