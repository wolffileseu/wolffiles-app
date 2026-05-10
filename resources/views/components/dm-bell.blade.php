{{-- DM (Private Messages) bell for navbar --}}
@auth
@php
    // Recent conversations for the dropdown preview (top 5)
    $dmRecent = \App\Models\Pm\PmParticipant::query()
        ->with(["conversation.latestMessage.sender:id,name", "conversation.participants.user:id,name"])
        ->where("user_id", auth()->id())
        ->whereNull("deleted_at")
        ->whereNull("left_at")
        ->whereHas("conversation", fn($q) => $q->whereNotNull("last_message_at"))
        ->get()
        ->sortByDesc(fn($p) => optional($p->conversation)->last_message_at)
        ->take(5);
@endphp
<div class="relative" x-data="{ dmOpen: false }">
    <button @click="dmOpen = !dmOpen" class="relative p-2 text-gray-400 hover:text-white transition-colors" title="{{ __('messages.dm_inbox') }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        @if(($dmUnreadCount ?? 0) > 0)
            <span class="absolute -top-1 -right-1 bg-amber-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                {{ $dmUnreadCount > 9 ? "9+" : $dmUnreadCount }}
            </span>
        @endif
    </button>

    <div x-show="dmOpen" @click.away="dmOpen = false" x-cloak x-transition
         class="absolute right-0 mt-2 w-96 bg-gray-900 rounded-xl border border-gray-700 shadow-2xl z-50 overflow-hidden">

        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700/80 bg-gray-800/60">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-white">{{ __('messages.dm_inbox') }}</h3>
                @if(($dmUnreadCount ?? 0) > 0)
                    <span class="text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-full px-1.5 py-0.5 font-semibold">{{ $dmUnreadCount }}</span>
                @endif
            </div>
            <a href="{{ route('dm.compose') }}" class="text-xs text-amber-400 hover:text-amber-300 transition-colors">
                + {{ __('messages.dm_new') }}
            </a>
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-gray-700/50">
            @forelse($dmRecent as $part)
                @php
                    $conv     = $part->conversation;
                    $latest   = $conv->latestMessage ?? null;
                    $isUnread = $part->hasUnread();
                    $others   = $conv->participants
                        ->where("user_id", "!=", auth()->id())
                        ->pluck("user.name")
                        ->filter()
                        ->take(2)
                        ->implode(", ");
                    $title    = $conv->subject ?: $others ?: __('messages.dm_no_subject');
                    $preview  = $latest && $latest->body ? mb_substr(strip_tags($latest->body), 0, 80) : "";
                    $initials = strtoupper(mb_substr($others ?: "?", 0, 2));
                    $colors   = ["bg-blue-600","bg-emerald-600","bg-violet-600","bg-rose-600","bg-amber-600","bg-cyan-600"];
                    $avColor  = $colors[abs(crc32($others ?: "x")) % count($colors)];
                @endphp
                <a href="{{ route('dm.show', $conv) }}"
                   class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-gray-800/60 {{ $isUnread ? 'bg-gray-800/40' : '' }}">
                    <div class="{{ $avColor }} w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5">
                        {{ $conv->isGroup() ? '👥' : $initials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold text-white truncate">{{ $title }}</span>
                            @if($conv->locked)
                                <span class="text-xs">🔒</span>
                            @endif
                        </div>
                        @if($preview)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $preview }}</p>
                        @endif
                        <p class="text-xs text-gray-600 mt-0.5">
                            @if($conv->last_message_at)
                                {{ $conv->last_message_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                    @if($isUnread)
                        <div class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0 mt-2" style="box-shadow:0 0 5px rgba(245,158,11,0.5)"></div>
                    @endif
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <p class="text-xs text-gray-500">{{ __('messages.dm_empty') }}</p>
                </div>
            @endforelse
        </div>

        <a href="{{ route('dm.inbox') }}"
           class="block text-center py-2.5 text-xs text-gray-400 hover:text-amber-400 border-t border-gray-700/80 bg-gray-800/40 transition-colors">
            {{ __('messages.dm_view_all') }} &rarr;
        </a>
    </div>
</div>
@endauth
