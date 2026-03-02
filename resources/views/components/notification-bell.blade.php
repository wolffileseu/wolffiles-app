{{-- Notification Bell for Navbar --}}
{{-- Usage: @include('components.notification-bell') --}}
@auth
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {{ auth()->user()->unreadNotifications->count() > 9 ? '9+' : auth()->user()->unreadNotifications->count() }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false" x-cloak x-transition
         class="absolute right-0 mt-2 w-80 bg-gray-800 rounded-lg border border-gray-700 shadow-xl z-50">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
            <h3 class="text-sm font-semibold text-white">{{ __('messages.notifications') }}</h3>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <form action="{{ route('notifications.markAllRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-amber-400 hover:text-amber-300">{{ __('messages.mark_all_read') }}</button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse(auth()->user()->notifications->take(10) as $notification)
                <a href="{{ route('notifications.read', $notification->id) }}"
                   class="block px-4 py-3 hover:bg-gray-700/50 transition-colors border-b border-gray-700/50
                          {{ $notification->read_at ? '' : 'bg-gray-750' }}">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-0.5">
                            @switch($notification->data['type'] ?? '')
                                @case('file_commented')
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    @break
                                @case('file_approved')
                                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @break
                                @case('file_milestone')
                                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    @break
                                @default
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @endswitch
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-300 truncate">
                                {{ $notification->data['message'] ?? $notification->data['comment_excerpt'] ?? $notification->data['file_title'] ?? '' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @unless($notification->read_at)
                            <span class="w-2 h-2 bg-amber-400 rounded-full flex-shrink-0 mt-1.5"></span>
                        @endunless
                    </div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-gray-500 text-sm">{{ __('messages.no_notifications') }}</div>
            @endforelse
        </div>

        @if(auth()->user()->notifications->count() > 10)
            <a href="{{ route('notifications.index') }}" class="block text-center py-3 text-sm text-amber-400 hover:text-amber-300 border-t border-gray-700">
                {{ __('messages.view_all_notifications') }}
            </a>
        @endif
    </div>
</div>
@endauth
