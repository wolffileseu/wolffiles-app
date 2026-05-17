<x-layouts.app :title="__('testserver.page_title')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('testserver.breadcrumb_home') }}</a> /
            <span class="text-gray-300">{{ __('testserver.breadcrumb_testserver') }}</span>
        </nav>

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                <span class="text-4xl">🎮</span>
                {{ __('testserver.page_heading') }}
            </h1>
            @if($settings->public_intro_text)
                <p class="mt-3 text-gray-400 max-w-3xl">{{ $settings->public_intro_text }}</p>
            @endif
        </div>

        {{-- Server Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($servers as $server)
                @php
                    $isAvailable = $server->isAvailable();
                    $isBusy = $server->isBusy();
                    $statusColor = match($server->status) {
                        'idle' => 'emerald',
                        'reserving', 'cleanup' => 'amber',
                        'active' => 'red',
                        default => 'gray',
                    };
                    $statusLabel = __('testserver.status_' . $server->status);
                @endphp

                <div class="bg-gray-800/60 backdrop-blur border border-gray-700 rounded-lg p-6
                            hover:border-{{ $statusColor }}-500/50 transition-all">

                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $server->name }}</h3>
                            <p class="text-sm text-gray-400 mt-1">
                                {{ __('testserver.card_slot_number', ['number' => $server->slot_number]) }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                                     bg-{{ $statusColor }}-500/20 text-{{ $statusColor }}-400">
                            <span class="w-2 h-2 rounded-full bg-{{ $statusColor }}-400 {{ $isBusy ? 'animate-pulse' : '' }}"></span>
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-400">
                            <span>{{ __('testserver.card_max_duration') }}:</span>
                            <span class="text-gray-200">
                                {{ __('testserver.card_minutes', ['min' => $server->max_session_minutes]) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>{{ __('testserver.card_max_players') }}:</span>
                            <span class="text-gray-200">{{ $server->max_players }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>{{ __('testserver.card_default_mod') }}:</span>
                            <span class="text-gray-200">{{ $server->default_mod }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>{{ __('testserver.card_total_sessions') }}:</span>
                            <span class="text-gray-200">{{ number_format($server->total_sessions) }}</span>
                        </div>
                    </div>

                    <div class="mt-5 pt-5 border-t border-gray-700">
                        @if($isAvailable)
                            <a href="{{ route('testserver.launch') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5
                                      bg-emerald-600 hover:bg-emerald-500 text-white font-semibold
                                      rounded-lg transition-colors">
                                🎮 {{ __('testserver.card_btn_start_session') }}
                            </a>
                        @else
                            <button disabled
                                class="w-full px-4 py-2.5 bg-gray-700 text-gray-500
                                       rounded-lg cursor-not-allowed font-medium">
                                {{ __('testserver.card_btn_busy') }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500">{{ __('testserver.no_servers_available') }}</p>
                </div>
            @endforelse
        </div>

        @if($settings->public_rules_text)
            <div class="mt-8 p-4 bg-amber-900/20 border border-amber-700/40 rounded-lg">
                <h3 class="text-sm font-semibold text-amber-400 mb-2">ℹ️ {{ __('testserver.rules_heading') }}</h3>
                <p class="text-sm text-gray-300 whitespace-pre-line">{{ $settings->public_rules_text }}</p>
            </div>
        @endif
    </div>
</x-layouts.app>
