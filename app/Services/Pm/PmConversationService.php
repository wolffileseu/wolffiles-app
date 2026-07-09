<?php

namespace App\Services\Pm;

use Illuminate\Database\UniqueConstraintViolationException;
use App\Exceptions\Pm\PmServiceException;
use App\Models\Pm\PmConversation;
use App\Models\Pm\PmMessage;
use App\Models\Pm\PmMessageEdit;
use App\Models\Pm\PmParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Core PM mechanics: create conversations, send/edit messages,
 * read state, soft delete per user.
 *
 * Permission/policy decisions are delegated to PmPolicyService.
 * Rate limiting is delegated to PmRateLimiter.
 */
class PmConversationService
{
    public function __construct(
        private PmPolicyService $policy,
        private PmRateLimiter $rateLimiter,
    ) {}

    // -----------------------------------------------------------------------
    // Conversation creation
    // -----------------------------------------------------------------------

    /**
     * Get the existing 1:1 conversation between two users, or create a new one.
     * Idempotent: calling twice returns the same conversation.
     *
     * Race-safe via UNIQUE index on hash_key + transactional lock.
     */
    public function findOrCreateDirect(User $a, User $b): PmConversation
    {
        if ($a->id === $b->id) {
            throw new PmServiceException("self_message", "Cannot start a conversation with yourself.");
        }

        $hash = PmConversation::hashFor([$a->id, $b->id]);

        // Fast path: already exists
        $existing = PmConversation::where("hash_key", $hash)->first();
        if ($existing) {
            return $existing;
        }

        // Slow path: create. Wrapped in transaction; if a concurrent request
        // wins the unique-index race, we catch and re-fetch.
        try {
            return DB::transaction(function () use ($a, $b, $hash) {
                $conv = PmConversation::create([
                    "type"       => "direct",
                    "hash_key"   => $hash,
                    "created_by" => $a->id,
                ]);

                PmParticipant::insert([
                    [
                        "conversation_id" => $conv->id,
                        "user_id"         => $a->id,
                        "joined_at"       => now(),
                        "is_creator"      => true,
                        "created_at"      => now(),
                        "updated_at"      => now(),
                    ],
                    [
                        "conversation_id" => $conv->id,
                        "user_id"         => $b->id,
                        "joined_at"       => now(),
                        "is_creator"      => false,
                        "created_at"      => now(),
                        "updated_at"      => now(),
                    ],
                ]);

                return $conv;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Concurrent insert: refetch
            return PmConversation::where("hash_key", $hash)->firstOrFail();
        }
    }

    /**
     * Create a group conversation with multiple participants.
     * Creator is included in the participants list automatically.
     *
     * @param array<int> $participantUserIds
     */
    public function createGroup(User $creator, array $participantUserIds, ?string $subject = null): PmConversation
    {
        // Sanitize: dedupe + remove self + validate
        $ids = array_values(array_unique(array_map("intval", $participantUserIds)));
        $ids = array_values(array_filter($ids, fn ($id) => $id !== $creator->id && $id > 0));

        if (count($ids) < 1) {
            throw new PmServiceException("group_needs_participants", "Group conversations need at least one other participant.");
        }

        $maxParticipants = (int) config("pm.max_participants_group", 10);
        // +1 because creator counts
        if (count($ids) + 1 > $maxParticipants) {
            throw new PmServiceException(
                "too_many_participants",
                "Maximum {$maxParticipants} participants allowed."
            );
        }

        // Verify all user IDs exist
        $existingCount = User::whereIn("id", $ids)->count();
        if ($existingCount !== count($ids)) {
            throw new PmServiceException("invalid_participants", "One or more participants do not exist.");
        }

        $this->rateLimiter->hit($creator, "new_conversation");

        return DB::transaction(function () use ($creator, $ids, $subject) {
            $conv = PmConversation::create([
                "type"       => "group",
                "subject"    => $subject ? mb_substr($subject, 0, (int) config("pm.max_subject_length", 200)) : null,
                "created_by" => $creator->id,
            ]);

            $rows = [];
            $rows[] = [
                "conversation_id" => $conv->id,
                "user_id"         => $creator->id,
                "joined_at"       => now(),
                "is_creator"      => true,
                "created_at"      => now(),
                "updated_at"      => now(),
            ];
            foreach ($ids as $uid) {
                $rows[] = [
                    "conversation_id" => $conv->id,
                    "user_id"         => $uid,
                    "joined_at"       => now(),
                    "is_creator"      => false,
                    "created_at"      => now(),
                    "updated_at"      => now(),
                ];
            }
            PmParticipant::insert($rows);

            return $conv;
        });
    }

    // -----------------------------------------------------------------------
    // Sending messages
    // -----------------------------------------------------------------------

    /**
     * Send a message to an existing conversation.
     *
     * Verifies sender is a participant, conversation not locked, rate limit OK.
     * Persists IP and User-Agent for audit purposes.
     */
    public function sendMessage(
        User $sender,
        PmConversation $conv,
        string $body,
        string $format = "markdown",
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): PmMessage {
        // Policy check
        $policyResult = $this->policy->canSendToConversation($sender, $conv);
        if (! $policyResult["ok"]) {
            throw new PmServiceException($policyResult["reason"]);
        }

        // For direct conversations, also check sender->recipient policy
        // (block list, recipient privacy settings)
        if ($conv->isDirect()) {
            $recipient = $conv->participants()
                ->where("user_id", "!=", $sender->id)
                ->first()?->user;

            if ($recipient) {
                $directPolicy = $this->policy->canSendTo($sender, $recipient);
                if (! $directPolicy["ok"]) {
                    throw new PmServiceException($directPolicy["reason"]);
                }
            }
        }

        // Validate body
        $body = trim($body);
        if ($body === "") {
            throw new PmServiceException("empty_body", "Message body cannot be empty.");
        }
        $maxLen = (int) config("pm.max_body_length", 10000);
        if (mb_strlen($body) > $maxLen) {
            throw new PmServiceException("body_too_long", "Message exceeds maximum length of {$maxLen} characters.");
        }

        if (! in_array($format, ["markdown", "plain"], true)) {
            $format = "markdown";
        }

        // Rate limit
        $this->rateLimiter->hit($sender, "send_message");

        // Persist
        return PmMessage::create([
            "conversation_id" => $conv->id,
            "sender_id"       => $sender->id,
            "body"            => $body,
            "body_format"     => $format,
            "ip_address"      => $ipAddress,
            "user_agent"      => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Editing messages
    // -----------------------------------------------------------------------

    /**
     * Edit an existing message.
     *
     * Constraints:
     *   - Only the original sender can edit
     *   - Within edit_window_minutes from creation
     *   - Conversation must not be locked
     *   - Old body is recorded in pm_message_edits (write-once history)
     */
    public function editMessage(User $editor, PmMessage $message, string $newBody, ?string $ipAddress = null): void
    {
        if ($message->sender_id !== $editor->id) {
            throw new PmServiceException("not_message_owner", "You can only edit your own messages.");
        }

        if ($message->isPurged()) {
            throw new PmServiceException("message_purged", "Cannot edit a purged message.");
        }

        $editWindow = (int) config("pm.edit_window_minutes", 15);
        $cutoff = $message->created_at->copy()->addMinutes($editWindow);
        if (now()->gt($cutoff)) {
            throw new PmServiceException(
                "edit_window_expired",
                "Edit window of {$editWindow} minutes has expired."
            );
        }

        // Check conversation lock
        if ($message->conversation->locked) {
            throw new PmServiceException("conversation_locked");
        }

        $newBody = trim($newBody);
        if ($newBody === "") {
            throw new PmServiceException("empty_body", "Message body cannot be empty.");
        }
        $maxLen = (int) config("pm.max_body_length", 10000);
        if (mb_strlen($newBody) > $maxLen) {
            throw new PmServiceException("body_too_long");
        }

        // No-op if unchanged
        if ($newBody === $message->body) {
            return;
        }

        DB::transaction(function () use ($message, $newBody, $ipAddress) {
            // Record old body in history
            PmMessageEdit::create([
                "message_id"     => $message->id,
                "old_body"       => $message->body,
                "edited_at"      => now(),
                "edited_from_ip" => $ipAddress,
                "created_at"     => now(),
            ]);

            // Update message
            $message->update([
                "body"      => $newBody,
                "edited_at" => now(),
            ]);
        });
    }

    // -----------------------------------------------------------------------
    // Read / unread state
    // -----------------------------------------------------------------------

    public function markAsRead(User $user, PmConversation $conv): void
    {
        PmParticipant::where("conversation_id", $conv->id)
            ->where("user_id", $user->id)
            ->update(["last_read_at" => now()]);
    }

    public function markAsUnread(User $user, PmConversation $conv): void
    {
        PmParticipant::where("conversation_id", $conv->id)
            ->where("user_id", $user->id)
            ->update(["last_read_at" => null]);
    }

    // -----------------------------------------------------------------------
    // Per-user soft delete (hide from inbox)
    // -----------------------------------------------------------------------

    /**
     * Hide the conversation from this user's inbox.
     * Other participants still see it. New messages re-show it.
     */
    public function softDeleteForUser(User $user, PmConversation $conv): void
    {
        PmParticipant::where("conversation_id", $conv->id)
            ->where("user_id", $user->id)
            ->update(["deleted_at" => now()]);
    }

    /**
     * Restore visibility for a user (e.g. when a new message arrives).
     */
    public function restoreForUser(User $user, PmConversation $conv): void
    {
        PmParticipant::where("conversation_id", $conv->id)
            ->where("user_id", $user->id)
            ->update(["deleted_at" => null]);
    }

    // -----------------------------------------------------------------------
    // Group participant management
    // -----------------------------------------------------------------------

    /**
     * Creator-only: add a new participant to a group conversation.
     */
    public function addParticipant(PmConversation $conv, User $newUser, User $addedBy): void
    {
        if (! $conv->isGroup()) {
            throw new PmServiceException("not_a_group", "Cannot add participants to a direct conversation.");
        }

        $addedByPivot = $conv->participants()->where("user_id", $addedBy->id)->first();
        if (! $addedByPivot || ! $addedByPivot->is_creator) {
            throw new PmServiceException("not_creator", "Only the conversation creator can add participants.");
        }

        if ($conv->hasParticipant($newUser->id)) {
            return; // idempotent
        }

        $maxParticipants = (int) config("pm.max_participants_group", 10);
        $current = $conv->activeParticipants()->count();
        if ($current >= $maxParticipants) {
            throw new PmServiceException("too_many_participants");
        }

        PmParticipant::create([
            "conversation_id" => $conv->id,
            "user_id"         => $newUser->id,
            "joined_at"       => now(),
            "is_creator"      => false,
        ]);
    }

    /**
     * Self-leave: a participant removes themselves from a group.
     */
    public function leaveConversation(User $user, PmConversation $conv): void
    {
        if (! $conv->isGroup()) {
            throw new PmServiceException("not_a_group", "Direct conversations cannot be left; soft-delete instead.");
        }

        PmParticipant::where("conversation_id", $conv->id)
            ->where("user_id", $user->id)
            ->whereNull("left_at")
            ->update(["left_at" => now()]);
    }
}
