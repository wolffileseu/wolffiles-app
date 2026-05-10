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
        // TODO: Phase 4d
        abort(501, "Not implemented yet (Phase 4d).");
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
        // TODO: Phase 4e
        abort(501, "Not implemented yet (Phase 4e).");
    }
}
