<x-layouts.app :title="$title">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-white">📬 {{ __('Postfach') }}</h1>
            <a href="{{ route('dm.compose') }}"
               class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                + {{ __('New message') }}
            </a>
        </div>

        @if(session('status'))
            <div class="bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            @if($participants->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <div class="text-5xl mb-4">📭</div>
                    <p>{{ __('No conversations yet.') }}</p>
                    <a href="{{ route('dm.compose') }}" class="inline-block mt-4 text-amber-400 hover:text-amber-300">
                        {{ __('Start a new conversation') }} →
                    </a>
                </div>
            @else
                <ul class="divide-y divide-gray-700">
                    @foreach($participants as $part)
                        @php
                            $conv     = $part->conversation;
                            $latest   = $conv->latestMessage;
                            $isUnread = $part->hasUnread();

                            // For direct convs: show the OTHER participant
                            // For groups: show all OTHER participants
                            $others = $conv->participants
                                ->where('user_id', '!=', auth()->id())
                                ->pluck('user.name')
                                ->filter()
                                ->take(3)
                                ->implode(', ');
                            if ($conv->isGroup() && $conv->participants->count() > 4) {
                                $others .= ' +' . ($conv->participants->count() - 4);
                            }
                        @endphp

                        <li>
                            <a href="{{ route('dm.show', $conv) }}"
                               class="flex items-start gap-4 p-4 hover:bg-gray-700/50 transition-colors {{ $isUnread ? 'bg-gray-700/30' : '' }}">

                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-700 flex items-center justify-center text-white font-bold text-sm">
                                    {{ $conv->isGroup() ? '👥' : strtoupper(mb_substr($others ?: '?', 0, 1)) }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline gap-3">
                                        <h3 class="font-semibold text-white truncate {{ $isUnread ? '' : 'text-gray-200' }}">
                                            {{ $conv->subject ?: $others ?: __('(no participants)') }}
                                        </h3>
                                        @if($conv->last_message_at)
                                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                                {{ $conv->last_message_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($conv->isGroup() && $conv->subject)
                                        <p class="text-xs text-gray-500 truncate">{{ $others }}</p>
                                    @endif

                                    @if($latest && $latest->body)
                                        <p class="text-sm text-gray-400 truncate mt-1">
                                            <span class="text-gray-500">{{ $latest->sender->name }}:</span>
                                            {{ $renderer->toPlainText($latest->body, 100) }}
                                        </p>
                                    @elseif($latest && $latest->isPurged())
                                        <p class="text-sm text-gray-500 italic mt-1">
                                            {{ __('Message expired (retention)') }}
                                        </p>
                                    @endif
                                </div>

                                @if($isUnread)
                                    <div class="flex-shrink-0">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500" title="{{ __('Unread') }}"></span>
                                    </div>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <p class="text-xs text-gray-500 mt-6 text-center">
            {{ __('Messages older than :days days are automatically deleted.', ['days' => config('pm.retention_body_days', 180)]) }}
        </p>
    </div>
</x-layouts.app>
