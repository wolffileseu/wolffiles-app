<x-layouts.app>
<div class="max-w-2xl mx-auto px-4 py-8">

    <a href="{{ route('hosting.server', $order) }}" class="text-amber-500 hover:text-amber-400 text-sm mb-4 inline-block">← {{ __('messages.hosting_back_to_server') }}</a>

    <h1 class="text-3xl font-bold mb-2">{{ __('messages.hosting_renew_server') }}</h1>
    <p class="text-gray-400 mb-8">{{ $order->server_name }} — {{ $order->slots }} {{ __('messages.hosting_slots') }}</p>

    @if($order->paid_until)
        <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4 mb-6 text-sm {{ $order->isExpired() ? 'border-red-500/50' : '' }}">
            @if($order->isExpired())
                <span class="text-red-400 font-bold">⚠️ {{ __('messages.hosting_expired_since', ['date' => $order->paid_until->format('d.m.Y')]) }}</span>
            @else
                <span class="text-gray-400">{{ __('messages.hosting_active_until', ['date' => $order->paid_until->format('d.m.Y H:i'), 'days' => $order->daysRemaining()]) }}</span>
            @endif
        </div>
    @endif

    <div class="space-y-3">
        @foreach(['daily' => [__('messages.hosting_1_day'), null], 'weekly' => [__('messages.hosting_7_days'), null], 'monthly' => [__('messages.hosting_30_days'), null], 'quarterly' => [__('messages.hosting_90_days'), __('messages.hosting_save_20')]] as $key => [$label, $badge])
        <form action="{{ route('hosting.checkout') }}" method="POST">
            @csrf
            <input type="hidden" name="product_id" value="{{ $order->product_id }}">
            <input type="hidden" name="slots" value="{{ $order->slots }}">
            <input type="hidden" name="period" value="{{ $key }}">
            <input type="hidden" name="server_name" value="{{ $order->server_name }}">
            <input type="hidden" name="mod" value="{{ $order->mod }}">
            <button type="submit"
                    class="w-full flex items-center justify-between bg-gray-800/50 border border-gray-700 hover:border-amber-500/50 rounded-xl p-5 transition-all text-left">
                <div>
                    <span class="text-white font-bold text-lg">{{ $label }}</span>
                    @if($badge)
                        <span class="ml-2 text-xs text-green-400 font-bold bg-green-500/10 px-2 py-0.5 rounded-full">{{ $badge }}</span>
                    @endif
                </div>
                <span class="text-amber-500 text-xl font-bold">{{ number_format($prices[$key], 2) }} €</span>
            </button>
        </form>
        @endforeach
    </div>

</div>
</x-layouts.app>
