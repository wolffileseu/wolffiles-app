<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Pm\PmConversation;
use App\Models\Pm\PmParticipant;
use App\Services\Pm\PmConversationService;
use App\Services\Pm\PmMarkdownRenderer;
use App\Services\Pm\PmPolicyService;
use App\Services\Pm\PmRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Frontend controller for Direct Messages (PM system).
 *
 * URL space: /dm/...
 * All routes require auth middleware.
 */
class DmController extends Controller
{
    public function __construct(
        private PmConversationService $conversations,
        private PmPolicyService $policy,
        private PmMarkdownRenderer $renderer,
        private PmRateLimiter $rateLimiter,
    ) {}

    /**
     * Inbox: list of conversations the user participates in.
     */
    public function inbox(Request $request)
    {
        $user = Auth::user();

        // Conversations where user is participant, not soft-deleted, not left
        $participants = PmParticipant::query()
            ->with([
                "conversation" => function ($q) {
                    $q->with([
                        "latestMessage.sender:id,name,avatar",
                        "participants" => function ($q2) {
                            $q2->whereNull("left_at")->with("user:id,name,avatar");
                        },
                    ]);
                },
            ])
            ->where("user_id", $user->id)
            ->whereNull("deleted_at")
            ->whereNull("left_at")
            ->whereHas("conversation")
            ->get()
            ->sortByDesc(fn ($p) => optional($p->conversation)->last_message_at)
            ->values();

        return view("frontend.dm.inbox", [
            "title"        => __("Postfach"),
            "participants" => $participants,
            "renderer"     => $this->renderer,
        ]);
    }

    /**
     * Show a single conversation with all its messages.
     */
    public function show(PmConversation $conversation)
    {
        $user = Auth::user();

        // Authorization: user must be a participant
        if (! $conversation->hasParticipant($user->id)) {
            abort(403, __("You are not a participant of this conversation."));
        }

        // Mark as read
        $this->conversations->markAsRead($user, $conversation);

        // Load messages with sender + edits
        $messages = $conversation->messages()
            ->with("sender:id,name,avatar")
            ->orderBy("created_at")
            ->get();

        $participants = $conversation->participants()
            ->with("user:id,name,avatar")
            ->whereNull("left_at")
            ->get();

        return view("frontend.dm.show", [
            "title"        => $conversation->subject ?? __("Conversation"),
            "conversation" => $conversation,
            "messages"     => $messages,
            "participants" => $participants,
            "renderer"     => $this->renderer,
        ]);
    }

    /**
     * Show compose form for a new direct message.
     * Phase 4d will fully implement this.
     */
    public function compose(Request $request)
    {
        $recipientId = $request->query("to");
        $recipient = $recipientId
            ? \App\Models\User::find($recipientId)
            : null;

        // Load all active users (excluding self) for the recipient suggestions datalist.
        // For wolffiles.eu small user-base (~ a few hundred max), this is fine.
        // If you scale to thousands of users, switch to AJAX search.
        $allUsers = \App\Models\User::query()
            ->select("id", "name")
            ->where("id", "!=", Auth::id())
            ->orderBy("name")
            ->get();

        return view("frontend.dm.compose", [
            "title"       => __("messages.dm_new"),
            "recipientId" => $recipientId,
            "recipient"   => $recipient,
            "allUsers"    => $allUsers,
        ]);
    }

    /**
     * Store a new message (reply or new conversation).
     * Phase 4d will fully implement this.
     */
    public function store(Request $request)
    {
        $sender = Auth::user();

        $validated = $request->validate([
            // Either recipient_id (from pre-filled link with ?to=) or recipient_name (typed)
            "recipient_id"   => "nullable|integer|exists:users,id",
            "recipient_name" => "nullable|string|max:255",
            "body"           => "required|string|min:1|max:" . config("pm.max_body_length", 10000),
            "subject"        => "nullable|string|max:" . config("pm.max_subject_length", 200),
        ], [
            "body.required" => __("messages.dm_body_required"),
            "body.max"      => __("messages.dm_body_too_long"),
        ]);

        // Resolve recipient: prefer ID, fallback to name lookup
        $recipient = null;
        if (! empty($validated["recipient_id"])) {
            $recipient = \App\Models\User::find($validated["recipient_id"]);
        } elseif (! empty($validated["recipient_name"])) {
            $recipient = \App\Models\User::where("name", $validated["recipient_name"])->first();
        }

        if (! $recipient) {
            return back()
                ->withInput()
                ->withErrors(["recipient_name" => __("messages.dm_recipient_not_found")]);
        }

        // Policy: can sender send to recipient?
        $policyResult = $this->policy->canSendTo($sender, $recipient);
        if (! $policyResult["ok"]) {
            return back()
                ->withInput()
                ->withErrors(["recipient_id" => __("messages.dm_reason_" . $policyResult["reason"])]);
        }

        // Find-or-create the 1:1 conversation
        try {
            $conversation = $this->conversations->findOrCreateDirect($sender, $recipient);
        } catch (\App\Exceptions\Pm\PmServiceException $e) {
            return back()
                ->withInput()
                ->withErrors(["recipient_id" => __("messages.dm_reason_" . $e->reasonCode)]);
        }

        // Send the message
        try {
            $this->conversations->sendMessage(
                $sender,
                $conversation,
                $validated["body"],
                "markdown",
                $request->ip(),
                $request->userAgent()
            );
        } catch (\App\Exceptions\Pm\RateLimitExceededException $e) {
            return back()
                ->withInput()
                ->withErrors(["body" => __("messages.dm_rate_limit", [
                    "minutes" => ceil($e->retryAfterSeconds / 60),
                ])]);
        } catch (\App\Exceptions\Pm\PmServiceException $e) {
            return back()
                ->withInput()
                ->withErrors(["body" => __("messages.dm_reason_" . $e->reasonCode)]);
        }

        // Invalidate the dmUnreadCount cache for the recipient (composer cache)
        \Illuminate\Support\Facades\Cache::forget("pm:unread_count:{$recipient->id}");

        return redirect()
            ->route("dm.show", $conversation)
            ->with("status", __("messages.dm_sent"));
    }

    /**
     * Reply to an existing conversation.
     * Phase 4d will fully implement this.
     */
    public function reply(Request $request, PmConversation $conversation)
    {
        $sender = Auth::user();

        // Authorization: must be a participant
        if (! $conversation->hasParticipant($sender->id)) {
            abort(403, __("messages.dm_reason_not_a_participant"));
        }

        $validated = $request->validate([
            "body" => "required|string|min:1|max:" . config("pm.max_body_length", 10000),
        ], [
            "body.required" => __("messages.dm_body_required"),
            "body.max"      => __("messages.dm_body_too_long"),
        ]);

        try {
            $this->conversations->sendMessage(
                $sender,
                $conversation,
                $validated["body"],
                "markdown",
                $request->ip(),
                $request->userAgent()
            );
        } catch (\App\Exceptions\Pm\RateLimitExceededException $e) {
            return back()
                ->withInput()
                ->withErrors(["body" => __("messages.dm_rate_limit", [
                    "minutes" => ceil($e->retryAfterSeconds / 60),
                ])]);
        } catch (\App\Exceptions\Pm\PmServiceException $e) {
            return back()
                ->withInput()
                ->withErrors(["body" => __("messages.dm_reason_" . $e->reasonCode)]);
        }

        // Invalidate dmUnreadCount cache for all OTHER participants
        foreach ($conversation->participants()->whereNull("left_at")->get() as $p) {
            if ($p->user_id !== $sender->id) {
                \Illuminate\Support\Facades\Cache::forget("pm:unread_count:{$p->user_id}");
            }
        }

        return redirect()
            ->route("dm.show", $conversation)
            ->with("status", __("messages.dm_sent"));
    }

    /**
     * Soft-delete a conversation from this user's inbox.
     * Phase 4d will fully implement this.
     */
    public function softDelete(PmConversation $conversation)
    {
        $user = Auth::user();
        $this->conversations->softDeleteForUser($user, $conversation);
        return redirect()->route("dm.inbox")->with("status", __("Conversation removed from inbox."));
    }

    /**
     * User settings: privacy + notifications.
     * Phase 4e will fully implement this.
     */
    public function settings()
    {
        $user = Auth::user();
        $settings = $this->policy->getOrCreateSettings($user);

        // Loaded blocks: blockerships I created
        $blocks = \App\Models\Pm\PmUserBlock::with("blocked:id,name")
            ->where("blocker_id", $user->id)
            ->orderByDesc("created_at")
            ->get();

        // Suggestion list for adding new blocks (excludes self + already-blocked)
        $alreadyBlockedIds = $blocks->pluck("blocked_id")->push($user->id)->all();
        $allUsers = \App\Models\User::query()
            ->select("id", "name")
            ->whereNotIn("id", $alreadyBlockedIds)
            ->orderBy("name")
            ->get();

        return view("frontend.dm.settings", [
            "title"    => __("messages.dm_settings"),
            "settings" => $settings,
            "blocks"   => $blocks,
            "allUsers" => $allUsers,
        ]);
    }

    public function updateSettings(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $settings = $this->policy->getOrCreateSettings($user);

        $validated = $request->validate([
            "who_can_message"               => "required|in:everyone,nobody",
            "email_notify"                  => "nullable|boolean",
            "discord_notify"                => "nullable|boolean",
            "telegram_notify"               => "nullable|boolean",
            "notification_throttle_minutes" => "required|integer|min:0|max:1440",
        ]);

        $settings->fill([
            "who_can_message"               => $validated["who_can_message"],
            "email_notify"                  => (bool) ($validated["email_notify"] ?? false),
            "discord_notify"                => (bool) ($validated["discord_notify"] ?? false),
            "telegram_notify"               => (bool) ($validated["telegram_notify"] ?? false),
            "notification_throttle_minutes" => (int) $validated["notification_throttle_minutes"],
        ])->save();

        return redirect()
            ->route("dm.settings")
            ->with("status", __("messages.dm_settings_saved"));
    }

    public function blockUser(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            "blocked_name" => "required|string|max:255",
            "reason"       => "nullable|string|max:200",
        ]);

        $target = \App\Models\User::where("name", $validated["blocked_name"])->first();

        if (! $target) {
            return back()->withErrors(["blocked_name" => __("messages.dm_recipient_not_found")]);
        }

        try {
            $this->policy->block($user, $target, $validated["reason"] ?? null);
        } catch (\App\Exceptions\Pm\PmServiceException $e) {
            return back()->withErrors(["blocked_name" => __("messages.dm_reason_" . $e->reasonCode)]);
        }

        return redirect()
            ->route("dm.settings")
            ->with("status", __("messages.dm_blocked", ["name" => $target->name]));
    }

    public function unblockUser(\App\Models\Pm\PmUserBlock $block)
    {
        $user = Auth::user();

        // Authorization: must be the blocker
        if ($block->blocker_id !== $user->id) {
            abort(403);
        }

        $blockedName = $block->blocked->name ?? __("messages.dm_user");
        $block->delete();

        return redirect()
            ->route("dm.settings")
            ->with("status", __("messages.dm_unblocked", ["name" => $blockedName]));
    }
}
