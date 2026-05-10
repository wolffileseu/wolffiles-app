<x-layouts.app :title="$title">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-4">
            <a href="{{ route('dm.inbox') }}" class="text-sm text-amber-400 hover:text-amber-300">
                &larr; {{ __('messages.dm_back_to_inbox') }}
            </a>
        </div>

        <h1 class="text-3xl font-bold text-white mb-6">&#9881; {{ __('messages.dm_settings') }}</h1>

        @if(session('status'))
            <div class="bg-green-900/30 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 mb-6">
                <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Privacy + Notifications --}}
        <form action="{{ route('dm.settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-6">

                {{-- Privacy --}}
                <div>
                    <h2 class="text-lg font-semibold text-white mb-3">&#128274; {{ __('messages.dm_privacy') }}</h2>
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 p-3 bg-gray-700/40 rounded-lg cursor-pointer hover:bg-gray-700/70 transition-colors">
                            <input type="radio" name="who_can_message" value="everyone"
                                   {{ old('who_can_message', $settings->who_can_message) === 'everyone' ? 'checked' : '' }}
                                   class="mt-1">
                            <div>
                                <div class="text-white text-sm font-medium">{{ __('messages.dm_privacy_everyone') }}</div>
                                <div class="text-gray-400 text-xs">{{ __('messages.dm_privacy_everyone_hint') }}</div>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3 bg-gray-700/40 rounded-lg cursor-pointer hover:bg-gray-700/70 transition-colors">
                            <input type="radio" name="who_can_message" value="nobody"
                                   {{ old('who_can_message', $settings->who_can_message) === 'nobody' ? 'checked' : '' }}
                                   class="mt-1">
                            <div>
                                <div class="text-white text-sm font-medium">{{ __('messages.dm_privacy_nobody') }}</div>
                                <div class="text-gray-400 text-xs">{{ __('messages.dm_privacy_nobody_hint') }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Notifications --}}
                <div>
                    <h2 class="text-lg font-semibold text-white mb-3">&#128276; {{ __('messages.dm_notifications') }}</h2>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="email_notify" value="1"
                                   {{ old('email_notify', $settings->email_notify) ? 'checked' : '' }}>
                            <span class="text-gray-300 text-sm">{{ __('messages.dm_notify_email') }}</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="discord_notify" value="1"
                                   {{ old('discord_notify', $settings->discord_notify) ? 'checked' : '' }}>
                            <span class="text-gray-300 text-sm">{{ __('messages.dm_notify_discord') }}</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="telegram_notify" value="1"
                                   {{ old('telegram_notify', $settings->telegram_notify) ? 'checked' : '' }}>
                            <span class="text-gray-300 text-sm">{{ __('messages.dm_notify_telegram') }}</span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label class="block text-gray-300 text-sm font-medium mb-2">
                            {{ __('messages.dm_throttle_minutes') }}
                        </label>
                        <input type="number" name="notification_throttle_minutes" min="0" max="1440"
                               value="{{ old('notification_throttle_minutes', $settings->notification_throttle_minutes) }}"
                               class="w-32 bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-1.5 text-sm">
                        <p class="text-xs text-gray-500 mt-1">{{ __('messages.dm_throttle_hint') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('messages.save') }}
                </button>
            </div>
        </form>

        {{-- Block list --}}
        <div class="mt-10">
            <h2 class="text-lg font-semibold text-white mb-3">&#128683; {{ __('messages.dm_block_list') }}</h2>

            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">

                {{-- Add a block --}}
                <div class="p-4 border-b border-gray-700">
                    <form action="{{ route('dm.blocks.store') }}" method="POST" class="flex flex-wrap gap-2 items-end">
                        @csrf
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs text-gray-400 mb-1">{{ __('messages.dm_block_user') }}</label>
                            <input type="text" name="blocked_name" list="block-user-list"
                                   value="{{ old('blocked_name') }}" required
                                   class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-1.5 text-sm"
                                   placeholder="{{ __('messages.dm_recipient_placeholder') }}"
                                   autocomplete="off">
                            <datalist id="block-user-list">
                                @foreach($allUsers as $u)
                                    <option value="{{ $u->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs text-gray-400 mb-1">{{ __('messages.dm_block_reason_optional') }}</label>
                            <input type="text" name="reason" maxlength="200"
                                   value="{{ old('reason') }}"
                                   class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-1.5 text-sm">
                        </div>
                        <button type="submit"
                                class="bg-red-700 hover:bg-red-800 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors">
                            {{ __('messages.dm_block_button') }}
                        </button>
                    </form>
                </div>

                {{-- List existing blocks --}}
                @if($blocks->isEmpty())
                    <div class="p-6 text-center text-gray-400 text-sm">
                        {{ __('messages.dm_no_blocks') }}
                    </div>
                @else
                    <ul class="divide-y divide-gray-700">
                        @foreach($blocks as $block)
                            <li class="flex items-center gap-3 p-4">
                                <div class="flex-1 min-w-0">
                                    <div class="text-white font-medium">{{ $block->blocked->name ?? __('messages.dm_user') }}</div>
                                    @if($block->reason)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $block->reason }}</div>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ __('messages.dm_blocked_at') }} {{ $block->created_at->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                                <form action="{{ route('dm.blocks.destroy', $block) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-amber-400 hover:text-amber-300">
                                        {{ __('messages.dm_unblock') }}
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
</x-layouts.app>
