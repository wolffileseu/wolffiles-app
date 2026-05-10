<?php

namespace App\Http\View\Composers;

use App\Models\Pm\PmParticipant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Injects PM-related data into the layout view:
 *   - $dmUnreadCount : number of conversations with unread messages
 *
 * Cached per-user for 30s to avoid hammering the DB on every page load.
 * Cache is invalidated by PmConversationService when read state changes
 * (TODO Phase 4c: hook cache invalidation into markAsRead).
 */
class DmComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            $view->with("dmUnreadCount", 0);
            return;
        }

        $cacheKey = "pm:unread_count:{$user->id}";
        $count = Cache::remember($cacheKey, 30, function () use ($user) {
            return PmParticipant::query()
                ->where("user_id", $user->id)
                ->whereNull("deleted_at")
                ->whereNull("left_at")
                ->whereHas("conversation", function ($q) use ($user) {
                    $q->whereNotNull("last_message_at")
                      ->whereRaw(
                          "last_message_at > COALESCE((SELECT last_read_at FROM pm_conversation_participants p WHERE p.conversation_id = pm_conversations.id AND p.user_id = ?), '1970-01-01')",
                          [$user->id]
                      );
                })
                ->count();
        });

        $view->with("dmUnreadCount", $count);
    }
}
