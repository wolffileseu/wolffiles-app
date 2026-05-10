<x-layouts.app :title="$title">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-4">
            <a href="{{ route('dm.inbox') }}" class="text-sm text-amber-400 hover:text-amber-300">
                &larr; {{ __('messages.dm_back_to_inbox') }}
            </a>
        </div>

        @if(session('status'))
            <div class="bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-4">
                {{ session('status') }}
            </div>
        @endif

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

            @if($conversation->locked)
                <div class="p-4 border-t border-gray-700 bg-gray-800/60">
                    <p class="text-sm text-red-400 italic text-center">
                        🔒 {{ __('messages.dm_reason_conversation_locked') }}
                    </p>
                </div>
            @else
                <div class="p-4 border-t border-gray-700 bg-gray-800/40">
                    <form action="{{ route('dm.reply', $conversation) }}" method="POST"
                          x-data="{ body: @js(old('body', '')), preview: false }">
                        @csrf

                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">{{ __('messages.dm_your_reply') }}</span>
                            <button type="button" @click="preview = !preview"
                                    class="text-xs text-amber-400 hover:text-amber-300"
                                    x-text="preview ? '{{ __('messages.dm_edit') }}' : '{{ __('messages.dm_preview') }}'"></button>
                        </div>

                        <textarea x-show="!preview" x-model="body"
                                  name="body"
                                  rows="4"
                                  maxlength="{{ config('pm.max_body_length', 10000) }}"
                                  class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 font-mono text-sm"
                                  placeholder="{{ __('messages.dm_message_placeholder') }}"
                                  required>{{ old('body') }}</textarea>

                        <div x-show="preview" x-cloak
                             class="min-h-[100px] bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 prose prose-invert prose-sm max-w-none whitespace-pre-wrap"
                             x-text="body || '{{ __('messages.dm_preview_empty') }}'"></div>

                        @if($errors->any())
                            <div class="mt-2 bg-red-900/50 border border-red-700 rounded-lg p-2">
                                <ul class="list-disc list-inside text-red-300 text-xs">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs text-gray-500">
                                {{ __('messages.dm_markdown_supported') }} &middot;
                                <span x-text="body.length"></span> / {{ config('pm.max_body_length', 10000) }}
                            </span>
                            <button type="submit"
                                    class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors">
                                {{ __('messages.dm_send') }} &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
