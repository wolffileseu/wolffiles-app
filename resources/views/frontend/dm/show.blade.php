<x-layouts.app :title="$title">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-4">
            <a href="{{ route('dm.inbox') }}" class="text-sm text-amber-400 hover:text-amber-300">
                ← {{ __('Back to inbox') }}
            </a>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-700 bg-gray-800/80">
                <h1 class="text-xl font-bold text-white">
                    {{ $conversation->subject ?: ($conversation->isGroup() ? __('Group conversation') : __('Direct message')) }}
                </h1>
                <p class="text-sm text-gray-400 mt-1">
                    @foreach($participants as $p)
                        <span>{{ $p->user->name }}</span>@if(!$loop->last), @endif
                    @endforeach
                </p>
                @if($conversation->locked)
                    <p class="text-xs text-red-400 mt-1">🔒 {{ __('This conversation is locked.') }}</p>
                @endif
            </div>

            <div class="divide-y divide-gray-700">
                @forelse($messages as $msg)
                    @php
                        $isMine = $msg->sender_id === auth()->id();
                    @endphp
                    <div class="p-4 {{ $isMine ? 'bg-gray-800/30' : '' }}">
                        <div class="flex items-baseline justify-between gap-3 mb-2">
                            <strong class="text-white">
                                {{ $msg->sender->name }}@if($isMine) <span class="text-xs text-gray-500 font-normal">({{ __('you') }})</span>@endif
                            </strong>
                            <span class="text-xs text-gray-500">
                                {{ $msg->created_at->format('Y-m-d H:i') }}
                                @if($msg->isEdited())
                                    · <em>{{ __('edited') }}</em>
                                @endif
                            </span>
                        </div>

                        @if($msg->isPurged())
                            <p class="text-gray-500 italic">
                                {{ __('Message expired (retention period of :days days).', ['days' => config('pm.retention_body_days', 180)]) }}
                            </p>
                        @else
                            <div class="prose prose-invert prose-sm max-w-none">
                                {!! $renderer->renderFor($msg->body, $msg->body_format) !!}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        {{ __('No messages.') }}
                    </div>
                @endforelse
            </div>

            <div class="p-4 border-t border-gray-700 bg-gray-800/60">
                <p class="text-sm text-gray-400 italic text-center">
                    {{ __('Reply form coming in Phase 4d.') }}
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
