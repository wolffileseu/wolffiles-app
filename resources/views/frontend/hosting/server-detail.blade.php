<x-layouts.app>
<div class="max-w-6xl mx-auto px-4 py-8">

    <a href="{{ route('hosting.dashboard') }}" class="text-amber-500 hover:text-amber-400 text-sm mb-4 inline-block">← {{ __('messages.hosting_back_to_servers') }}</a>

    @php $badge = $order->getStatusBadge(); @endphp
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold">{{ $order->server_name }}</h1>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $badge['color'] === 'success' ? 'bg-green-500/20 text-green-400' : '' }}
                        {{ $badge['color'] === 'warning' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                        {{ $badge['color'] === 'danger' ? 'bg-red-500/20 text-red-400' : '' }}
                        {{ $badge['color'] === 'gray' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                        {{ $badge['label'] }}
                    </span>
                </div>
                <p class="text-gray-400 text-sm mt-1">{{ $order->getGameDisplayName() }} · {{ $order->getModDisplayName() }} · {{ $order->slots }} {{ __('messages.hosting_slots') }}</p>
            </div>
            @if($order->isActive())
            <div class="flex gap-2">
                <form action="{{ route('hosting.server.action', $order) }}" method="POST" class="inline">@csrf<input type="hidden" name="action" value="restart">
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold transition-colors">🔄 {{ __('messages.hosting_restart') }}</button>
                </form>
                <form action="{{ route('hosting.server.action', $order) }}" method="POST" class="inline">@csrf<input type="hidden" name="action" value="stop">
                    <button class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg text-sm font-semibold transition-colors">⏹ {{ __('messages.hosting_stop') }}</button>
                </form>
                <form action="{{ route('hosting.server.action', $order) }}" method="POST" class="inline">@csrf<input type="hidden" name="action" value="start">
                    <button class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg text-sm font-semibold transition-colors">▶ {{ __('messages.hosting_start') }}</button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            {{-- Connection Info --}}
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_connection_data') }}</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400 block">{{ __('messages.hosting_ip_port') }}</span>
                        <span class="text-white font-mono">{{ $order->ip_address ?: __('messages.hosting_being_assigned') }}:{{ $order->port ?: '---' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">{{ __('messages.hosting_rcon_password') }}</span>
                        <span class="text-white font-mono" x-data="{ show: false }">
                            <span x-show="!show">••••••••</span>
                            <span x-show="show">{{ $order->rcon_password }}</span>
                            <button @click="show = !show" class="ml-2 text-amber-500 text-xs">
                                <span x-show="!show">{{ __('messages.hosting_show') }}</span>
                                <span x-show="show">{{ __('messages.hosting_hide') }}</span>
                            </button>
                        </span>
                    </div>
                    @if($order->ip_address)
                    <div>
                        <span class="text-gray-400 block">{{ __('messages.hosting_connect') }}</span>
                        <a href="{{ $order->getConnectUrl() }}" class="text-amber-500 hover:text-amber-400 font-mono text-xs">{{ $order->getConnectUrl() }}</a>
                    </div>
                    @endif
                    <div>
                        <span class="text-gray-400 block">{{ __('messages.hosting_node') }}</span>
                        <span class="text-white">{{ $order->node ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Console --}}
            @if($order->isActive())
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_console') }}</h3>
                <form action="{{ route('hosting.server.command', $order) }}" method="POST" class="flex gap-2">@csrf
                    <input type="text" name="command" placeholder="{{ __('messages.hosting_enter_command') }}"
                           class="flex-1 bg-gray-900 border border-gray-600 rounded-lg px-4 py-2 text-white font-mono text-sm placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-sm font-semibold transition-colors">{{ __('messages.hosting_send') }}</button>
                </form>
            </div>
            @endif

            {{-- Activity Log --}}
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_activities') }}</h3>
                @if($activities->isEmpty())
                    <p class="text-gray-500 text-sm">{{ __('messages.hosting_no_activities') }}</p>
                @else
                    <div class="space-y-2">
                        @foreach($activities as $log)
                        <div class="flex items-center gap-3 text-sm">
                            <span>{{ $log->getActionEmoji() }}</span>
                            <span class="text-gray-300">{{ $log->action }}</span>
                            <span class="text-gray-500 ml-auto text-xs">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            {{-- Billing --}}
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_billing') }}</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('messages.hosting_price') }}</span>
                        <span class="text-white font-bold">{{ number_format($order->price_paid, 2) }}€</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('messages.hosting_period') }}</span>
                        <span class="text-white">{{ __('messages.hosting_' . $order->billing_period) }}</span>
                    </div>
                    @if($order->paid_until)
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('messages.hosting_valid_until') }}</span>
                        <span class="{{ $order->daysRemaining() <= 3 ? 'text-red-400' : 'text-white' }}">{{ $order->paid_until->format('d.m.Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('messages.hosting_remaining') }}</span>
                        <span class="{{ $order->daysRemaining() <= 3 ? 'text-red-400 font-bold' : 'text-white' }}">{{ __('messages.hosting_days', ['count' => $order->daysRemaining()]) }}</span>
                    </div>
                    @endif
                </div>
                <a href="{{ route('hosting.renew', $order) }}" class="block w-full text-center bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 rounded-lg mt-4 transition-colors text-sm">{{ __('messages.hosting_renew') }}</a>
            </div>

            {{-- Specs --}}
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_specs') }}</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('messages.hosting_game') }}</span><span class="text-white">{{ $order->getGameDisplayName() }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('messages.hosting_mod') }}</span><span class="text-white">{{ $order->getModDisplayName() }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('messages.hosting_slots') }}</span><span class="text-white">{{ $order->slots }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('messages.hosting_location') }}</span><span class="text-white">🇩🇪 {{ __('messages.hosting_location_de') }}</span></div>
                </div>
            </div>

            {{-- Backups --}}
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_backups') }}</h3>
                @if($backups->isEmpty())
                    <p class="text-gray-500 text-sm">{{ __('messages.hosting_no_backups') }}</p>
                @else
                    <div class="space-y-2">
                        @foreach($backups as $backup)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-300">{{ $backup->name }}</span>
                            <span class="text-gray-500 text-xs">{{ $backup->created_at->format('d.m.') }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
