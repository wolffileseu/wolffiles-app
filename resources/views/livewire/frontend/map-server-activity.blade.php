<div>
    @if($this->hasAnyMapData && ($this->currentServers->isNotEmpty() || $this->recentServers->isNotEmpty()))
        <div class="mb-6 bg-gray-800 rounded-lg border border-gray-700 overflow-hidden" wire:poll.30s>
            <div class="px-5 py-3 border-b border-gray-700 flex items-center justify-between bg-gray-800/50">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <h3 class="text-base font-semibold text-white">{{ __('messages.map_server_activity_title') }}</h3>
                </div>
                <span class="text-xs text-gray-500">{{ __('messages.map_server_activity_auto_refresh') }}</span>
            </div>

            @if($this->currentServers->isNotEmpty())
                <div class="px-5 py-4">
                    <div class="flex items-center space-x-2 mb-3">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <h4 class="text-sm font-semibold text-green-400 uppercase tracking-wide">
                            {{ __('messages.map_server_activity_currently_playing') }}
                            <span class="text-gray-500 font-normal normal-case">({{ $this->currentServers->count() }})</span>
                        </h4>
                    </div>
                    <div class="space-y-2">
                        @foreach($this->currentServers as $server)
                            @include('livewire.frontend.partials.map-server-activity-row', ['server' => $server, 'isLive' => true])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($this->recentServers->isNotEmpty())
                <div class="px-5 py-4 {{ $this->currentServers->isNotEmpty() ? 'border-t border-gray-700/50' : '' }}">
                    <div class="flex items-center space-x-2 mb-3">
                        <svg class="w-3 h-3 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">
                            {{ __('messages.map_server_activity_recently_played') }}
                            <span class="text-gray-500 font-normal normal-case">({{ $this->recentServers->count() }})</span>
                        </h4>
                    </div>
                    <div class="space-y-2">
                        @foreach($this->recentServers as $server)
                            @include('livewire.frontend.partials.map-server-activity-row', ['server' => $server, 'isLive' => false])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
