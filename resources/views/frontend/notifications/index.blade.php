<x-layouts.app :title="__('messages.notifications')">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-white">{{ __('messages.notifications') }}</h1>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <form action="{{ route('notifications.markAllRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-amber-400 hover:text-amber-300">{{ __('messages.mark_all_read') }}</button>
                </form>
            @endif
        </div>

        <div class="space-y-2">
            @forelse($notifications as $notification)
                <a href="{{ route('notifications.read', $notification->id) }}"
                   class="block bg-gray-800 rounded-lg border border-gray-700 p-4 hover:bg-gray-750 transition-colors
                          {{ $notification->read_at ? 'opacity-75' : '' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm text-gray-200">{{ $notification->data['message'] ?? $notification->data['file_title'] ?? '' }}</p>
                            @if(isset($notification->data['comment_excerpt']))
                                <p class="text-xs text-gray-400 mt-1">"{{ $notification->data['comment_excerpt'] }}"</p>
                            @endif
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <span class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                            @unless($notification->read_at)
                                <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                            @endunless
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center text-gray-400 py-12">{{ __('messages.no_notifications') }}</div>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-layouts.app>
