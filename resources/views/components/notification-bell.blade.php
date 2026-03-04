{{-- Notification Bell for Navbar --}}
@auth
@php
    $bellNotifications = auth()->user()->notifications->take(10);
    $unreadCount = auth()->user()->unreadNotifications->count();
@endphp
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false" x-cloak x-transition
         class="absolute right-0 mt-2 w-96 bg-gray-900 rounded-xl border border-gray-700 shadow-2xl z-50 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700/80 bg-gray-800/60">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-white">{{ __('messages.notifications') }}</h3>
                @if($unreadCount > 0)
                    <span class="text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-full px-1.5 py-0.5 font-semibold">{{ $unreadCount }}</span>
                @endif
            </div>
            @if($unreadCount > 0)
                <form action="{{ route('notifications.markAllRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-amber-400 transition-colors">{{ __('messages.mark_all_read') }}</button>
                </form>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-700/50">
            @forelse($bellNotifications as $notification)
                @php
                    $data      = $notification->data;
                    $isUnread  = is_null($notification->read_at);
                    $type      = class_basename($notification->type);
                    $isComment = $type === 'NewComment';
                    $isUpload  = $type === 'NewFileUploaded';

                    if ($isComment) {
                        $actor   = $data['commenter_name'] ?? '?';
                        $subject = $data['commentable_title'] ?? '';
                        $preview = $data['body'] ?? '';
                    } elseif ($isUpload) {
                        $actor   = $data['uploader_name'] ?? '?';
                        $subject = $data['file_title'] ?? '';
                        $preview = '';
                    } else {
                        $actor   = $data['actor_name'] ?? $data['uploader_name'] ?? $data['commenter_name'] ?? '?';
                        $subject = $data['file_title'] ?? $data['commentable_title'] ?? '';
                        $preview = $data['body'] ?? $data['message'] ?? '';
                    }

                    $initials = strtoupper(mb_substr(strip_tags($actor), 0, 2));
                    $colors = ['bg-blue-600','bg-emerald-600','bg-violet-600','bg-rose-600','bg-amber-600','bg-cyan-600'];
                    $avatarColor = $colors[abs(crc32($actor)) % count($colors)];
                @endphp

                <a href="{{ route('notifications.read', $notification->id) }}"
                   class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-gray-800/60
                          {{ $isUnread ? 'bg-gray-800/40' : '' }}">

                    {{-- Avatar --}}
                    <div class="{{ $avatarColor }} w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5">
                        {{ $initials }}
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if($isComment)
                                <span class="text-blue-400" title="Comment">
                                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                </span>
                            @elseif($isUpload)
                                <span class="text-emerald-400" title="New Map">
                                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </span>
                            @endif
                            <span class="text-xs font-semibold text-white">{{ $actor }}</span>
                            <span class="text-xs text-gray-500">{{ $isComment ? 'on' : ($isUpload ? 'uploaded' : '') }}</span>
                            <span class="text-xs text-amber-400/80 truncate max-w-[120px]">{{ $subject }}</span>
                        </div>
                        @if($preview)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $preview }}</p>
                        @endif
                        <p class="text-xs text-gray-600 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>

                    @if($isUnread)
                        <div class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0 mt-2" style="box-shadow:0 0 5px rgba(245,158,11,0.5)"></div>
                    @endif
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <svg class="mx-auto w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-xs text-gray-500">{{ __('messages.no_notifications') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <a href="{{ route('notifications.index') }}"
           class="block text-center py-2.5 text-xs text-gray-400 hover:text-amber-400 border-t border-gray-700/80 bg-gray-800/40 transition-colors">
            {{ __('messages.view_all_notifications') }} →
        </a>
    </div>
</div>
@endauth
