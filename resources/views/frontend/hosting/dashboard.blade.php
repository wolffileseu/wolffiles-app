<x-layouts.app>
<div class="max-w-6xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold">🖥️ {{ __('messages.hosting_my_servers') }}</h1>
        <a href="{{ route('hosting.index') }}" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            {{ __('messages.hosting_new_server') }}
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-12 text-center">
            <span class="text-6xl block mb-4">🎮</span>
            <h2 class="text-xl font-bold mb-2">{{ __('messages.hosting_no_servers') }}</h2>
            <p class="text-gray-400 mb-6">{{ __('messages.hosting_no_servers_desc') }}</p>
            <a href="{{ route('hosting.index') }}" class="inline-block bg-amber-600 hover:bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                {{ __('messages.hosting_rent_server') }} →
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($orders as $order)
            @php $badge = $order->getStatusBadge(); @endphp
            <a href="{{ route('hosting.server', $order) }}"
               class="block bg-gray-800/50 border border-gray-700 rounded-xl p-5 hover:border-amber-500/50 transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="text-3xl">
                            @if($order->game === 'etl') 🎮 @elseif($order->game === 'et') 🕹️ @else 🏰 @endif
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-white text-lg">{{ $order->server_name }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $badge['color'] === 'success' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $badge['color'] === 'warning' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $badge['color'] === 'danger' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $badge['color'] === 'gray' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                                    {{ $badge['label'] }}
                                </span>
                            </div>
                            <p class="text-gray-400 text-sm">
                                {{ $order->getGameDisplayName() }} · {{ $order->getModDisplayName() }} · {{ $order->slots }} {{ __('messages.hosting_slots') }}
                                @if($order->ip_address)
                                    · <span class="text-gray-500 font-mono text-xs">{{ $order->ip_address }}:{{ $order->port }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        @if($order->paid_until)
                            <div class="text-sm {{ $order->daysRemaining() <= 3 ? 'text-red-400' : ($order->daysRemaining() <= 7 ? 'text-yellow-400' : 'text-gray-400') }}">
                                @if($order->isExpired())
                                    ⚠️ {{ __('messages.hosting_expired') }}
                                @else
                                    {{ __('messages.hosting_days_remaining', ['days' => $order->daysRemaining()]) }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">{{ __('messages.hosting_until', ['date' => $order->paid_until->format('d.m.Y')]) }}</div>
                        @endif
                        <div class="text-amber-400 font-bold mt-1">{{ number_format($order->price_paid, 2) }}€</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    @endif

</div>
</x-layouts.app>
