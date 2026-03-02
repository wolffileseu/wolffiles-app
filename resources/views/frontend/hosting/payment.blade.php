<x-layouts.app>
<div class="max-w-2xl mx-auto px-4 py-8">

    <a href="{{ route('hosting.dashboard') }}" class="text-amber-500 hover:text-amber-400 text-sm mb-4 inline-block">← {{ __('messages.hosting_back_to_servers') }}</a>

    <h1 class="text-3xl font-bold mb-2">{{ __('messages.hosting_complete_order') }}</h1>
    <p class="text-gray-400 mb-8">{{ __('messages.hosting_pay_subtitle') }}</p>

    {{-- Order Summary --}}
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6 mb-6">
        <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_order_summary') }}</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('messages.hosting_server') }}</span>
                <span class="text-white font-bold">{{ $order->server_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('messages.hosting_game') }}</span>
                <span class="text-white">{{ $order->getGameDisplayName() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('messages.hosting_mod') }}</span>
                <span class="text-white">{{ $order->getModDisplayName() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('messages.hosting_slots') }}</span>
                <span class="text-white">{{ $order->slots }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">{{ __('messages.hosting_duration') }}</span>
                <span class="text-white">
                    {{ match($order->billing_period) {
                        'daily' => __('messages.hosting_1_day'),
                        'weekly' => __('messages.hosting_7_days'),
                        'monthly' => __('messages.hosting_30_days'),
                        'quarterly' => __('messages.hosting_90_days'),
                        default => $order->billing_period,
                    } }}
                </span>
            </div>
            <hr class="border-gray-700">
            <div class="flex justify-between items-baseline">
                <span class="text-gray-400 text-base">{{ __('messages.hosting_total_price') }}</span>
                <div class="text-right">
                    <span class="text-amber-500 text-2xl font-bold">{{ number_format($invoice->amount, 2) }} €</span>
                    @if(in_array($order->billing_period, ['monthly', 'quarterly']))
                        <div class="text-xs text-gray-500">{{ __('messages.hosting_auto_renew') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- PayPal --}}
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6 mb-6">
        <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_payment_via_paypal') }}</h3>

        @if(in_array($order->billing_period, ['monthly', 'quarterly']))
            {{-- PayPal Subscription --}}
            <form action="https://www.paypal.com/cgi-bin/webscr" method="POST" id="paypal-form">
                <input type="hidden" name="cmd" value="_xclick-subscriptions">
                <input type="hidden" name="business" value="{{ $paypalEmail }}">
                <input type="hidden" name="item_name" value="Wolffiles.eu - {{ $order->server_name }} ({{ $order->slots }} Slots)">
                <input type="hidden" name="item_number" value="SERVER-{{ $order->id }}">
                <input type="hidden" name="no_shipping" value="1">
                <input type="hidden" name="no_note" value="1">
                <input type="hidden" name="currency_code" value="EUR">
                <input type="hidden" name="custom" value="{{ json_encode(['order_id' => $order->id, 'invoice_id' => $invoice->id, 'type' => 'hosting']) }}">
                <input type="hidden" name="a3" value="{{ number_format($invoice->amount, 2, '.', '') }}">
                <input type="hidden" name="p3" value="{{ $order->billing_period === 'monthly' ? '1' : '3' }}">
                <input type="hidden" name="t3" value="M">
                <input type="hidden" name="src" value="1">
                <input type="hidden" name="sra" value="1">
                <input type="hidden" name="notify_url" value="{{ route('hosting.paypal.ipn') }}">
                <input type="hidden" name="return" value="{{ route('hosting.payment.success', $order) }}">
                <input type="hidden" name="cancel_return" value="{{ route('hosting.payment', $order) }}">

                <button type="submit" class="w-full bg-[#0070ba] hover:bg-[#003087] text-white font-bold py-4 rounded-xl transition-colors flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106z"/>
                    </svg>
                    {{ __('messages.hosting_start_subscription') }} — {{ number_format($invoice->amount, 2) }}€{{ $order->billing_period === 'monthly' ? __('messages.hosting_per_month_short') : __('messages.hosting_per_quarter') }}
                </button>
            </form>

            <div class="mt-3 p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                <p class="text-xs text-blue-300">
                    🔄 {{ __('messages.hosting_subscription_info', ['period' => $order->billing_period === 'monthly' ? __('messages.hosting_monthly_auto') : __('messages.hosting_quarterly_auto')]) }}
                </p>
            </div>

        @else
            {{-- PayPal One-Time --}}
            <form action="https://www.paypal.com/cgi-bin/webscr" method="POST" id="paypal-form">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="{{ $paypalEmail }}">
                <input type="hidden" name="item_name" value="Wolffiles.eu - {{ $order->server_name }} ({{ $order->slots }} Slots, {{ $order->billing_period }})">
                <input type="hidden" name="item_number" value="SERVER-{{ $order->id }}">
                <input type="hidden" name="amount" value="{{ number_format($invoice->amount, 2, '.', '') }}">
                <input type="hidden" name="currency_code" value="EUR">
                <input type="hidden" name="no_shipping" value="1">
                <input type="hidden" name="no_note" value="1">
                <input type="hidden" name="custom" value="{{ json_encode(['order_id' => $order->id, 'invoice_id' => $invoice->id, 'type' => 'hosting']) }}">
                <input type="hidden" name="notify_url" value="{{ route('hosting.paypal.ipn') }}">
                <input type="hidden" name="return" value="{{ route('hosting.payment.success', $order) }}">
                <input type="hidden" name="cancel_return" value="{{ route('hosting.payment', $order) }}">

                <button type="submit" class="w-full bg-[#0070ba] hover:bg-[#003087] text-white font-bold py-4 rounded-xl transition-colors flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106z"/>
                    </svg>
                    {{ __('messages.hosting_pay_with_paypal') }} — {{ number_format($invoice->amount, 2) }} €
                </button>
            </form>

            <div class="mt-3 p-3 bg-gray-700/30 border border-gray-600 rounded-lg">
                <p class="text-xs text-gray-400">💳 {{ __('messages.hosting_one_time_info') }}</p>
            </div>
        @endif

        <p class="text-xs text-gray-500 text-center mt-4">{{ __('messages.hosting_redirect_paypal') }}</p>
    </div>

    {{-- Info --}}
    <div class="bg-gray-800/30 border border-gray-700 rounded-xl p-4 text-sm text-gray-400 space-y-2">
        <p>⚡ {{ __('messages.hosting_instant_creation') }}</p>
        <p>📧 {{ __('messages.hosting_email_confirmation') }}</p>
        <p>🔒 {{ __('messages.hosting_secure_payment') }}</p>
    </div>

</div>
</x-layouts.app>
