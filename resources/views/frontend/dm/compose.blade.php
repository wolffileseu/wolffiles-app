<x-layouts.app :title="$title">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-4">
            <a href="{{ route('dm.inbox') }}" class="text-sm text-amber-400 hover:text-amber-300">
                &larr; {{ __('messages.dm_back_to_inbox') }}
            </a>
        </div>

        <h1 class="text-3xl font-bold text-white mb-6">&#9999;&#65039; {{ __('messages.dm_new') }}</h1>

        <form action="{{ route('dm.store') }}" method="POST" class="space-y-4" x-data="{ body: @js(old('body', '')), preview: false }">
            @csrf

            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">

                {{-- Recipient --}}
                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">
                        {{ __('messages.dm_recipient') }} *
                    </label>
                    @if($recipient)
                        {{-- Pre-filled from ?to= param: show name, hidden id --}}
                        <div class="flex items-center gap-3 bg-gray-700 rounded-lg px-4 py-2">
                            <span class="text-white">{{ $recipient->name }}</span>
                            <a href="{{ route('dm.compose') }}" class="text-xs text-gray-400 hover:text-amber-400 ml-auto">
                                {{ __('messages.dm_change') }}
                            </a>
                        </div>
                        <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                    @else
                        {{-- Free-text with datalist - server resolves name -> id on submit --}}
                        <input type="text"
                               name="recipient_name"
                               list="recipient-list"
                               value="{{ old('recipient_name') }}"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                               placeholder="{{ __('messages.dm_recipient_placeholder') }}"
                               autocomplete="off"
                               required>
                        <datalist id="recipient-list">
                            @foreach($allUsers as $u)
                                <option value="{{ $u->name }}"></option>
                            @endforeach
                        </datalist>
                        <p class="text-xs text-gray-500 mt-1">{{ __('messages.dm_recipient_hint') }}</p>
                    @endif
                </div>

                {{-- Body with Markdown preview --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-gray-300 text-sm font-medium">
                            {{ __('messages.dm_message') }} *
                        </label>
                        <button type="button" @click="preview = !preview"
                                class="text-xs text-amber-400 hover:text-amber-300"
                                x-text="preview ? '{{ __('messages.dm_edit') }}' : '{{ __('messages.dm_preview') }}'"></button>
                    </div>

                    {{-- Edit mode --}}
                    <textarea x-show="!preview" x-model="body"
                              name="body"
                              rows="10"
                              maxlength="{{ config('pm.max_body_length', 10000) }}"
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 font-mono text-sm"
                              placeholder="{{ __('messages.dm_message_placeholder') }}"
                              required>{{ old('body') }}</textarea>

                    {{-- Preview mode (server-side render needed; we do a simple client-only escape preview) --}}
                    <div x-show="preview" x-cloak
                         class="min-h-[240px] bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 prose prose-invert prose-sm max-w-none whitespace-pre-wrap"
                         x-text="body || '{{ __('messages.dm_preview_empty') }}'"></div>

                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('messages.dm_markdown_supported') }}
                        &middot;
                        <span x-text="body.length"></span> / {{ config('pm.max_body_length', 10000) }}
                    </p>
                </div>
            </div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="bg-red-900/50 border border-red-700 rounded-lg p-4">
                    <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-between items-center">
                <a href="{{ route('dm.inbox') }}" class="text-sm text-gray-400 hover:text-gray-300">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('messages.dm_send') }} &rarr;
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
