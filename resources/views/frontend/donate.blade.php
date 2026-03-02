<x-layouts.app :title="__('messages.donate_title')">
<div class="max-w-5xl mx-auto px-4 py-12">

    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 mb-6">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </div>
        <h1 class="text-4xl font-bold text-white mb-3">{{ __('messages.donate_title') }}</h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto">{{ __('messages.donate_subtitle') }}</p>
    </div>

    {{-- Monthly Progress --}}
    <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-2xl p-8 mb-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-white">{{ __('messages.yearly_goal') }}</h2>
            @php
                $yearlyGoal = $monthlyGoal * 12;
                $yearlyTotal = \App\Models\Donation::completed()->whereYear("created_at", now()->year)->sum("amount");
                $yearlyPercent = $yearlyGoal > 0 ? min(100, round(($yearlyTotal / $yearlyGoal) * 100)) : 0;
            @endphp
            <span class="text-2xl font-bold text-amber-400">€{{ number_format($yearlyTotal, 2) }} <span class="text-gray-500 text-lg font-normal">/ €{{ number_format($yearlyGoal, 2) }}</span></span>
        </div>
        <div style="width:100%;background:#374151;border-radius:9999px;height:24px;overflow:hidden;position:relative;">
            <div style="width:{{ max($yearlyPercent, 3) }}%;height:100%;border-radius:9999px;background:linear-gradient(to right,#f59e0b,#ea580c);position:relative;">
                <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;text-shadow:0 1px 2px rgba(0,0,0,0.5);">{{ $yearlyPercent }}%</span>
            </div>
        </div>
        <div class="flex justify-between mt-2 text-sm text-gray-500">
            <span>{{ now()->translatedFormat('Y') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Donate Form (Left, 2 cols) --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Why Donate --}}
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-2xl p-8">
                <h2 class="text-xl font-semibold text-white mb-4">{{ __('messages.donate_why') }}</h2>
                <p class="text-gray-400 leading-relaxed mb-6">{{ __('messages.donate_why_text') }}</p>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-900/50 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-amber-400">€{{ number_format($totalAllTime, 0) }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('messages.total_donated') }}</div>
                    </div>
                    <div class="bg-gray-900/50 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-amber-400">{{ $totalDonors }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __('messages.total_donors') }}</div>
                    </div>
                </div>
            </div>

            {{-- PayPal Donate --}}
            @if($paypalEnabled && $paypalEmail)
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-2xl p-8" x-data="{ amount: 10, custom: false }">
                <h2 class="text-xl font-semibold text-white mb-6">{{ __('messages.donate_via_paypal') }}</h2>

                {{-- Amount Selection --}}
                <div class="mb-6">
                    <label class="text-sm text-gray-400 mb-3 block">{{ __('messages.donate_amount') }}</label>
                    <div class="grid grid-cols-4 gap-3">
                        @foreach([5, 10, 25, 50] as $amt)
                        <button @click="amount = {{ $amt }}; custom = false"
                                :class="amount == {{ $amt }} && !custom ? 'bg-amber-600 text-white border-amber-500' : 'bg-gray-900/50 text-gray-300 border-gray-600 hover:border-amber-500'"
                                class="border rounded-xl py-3 text-lg font-semibold transition">
                            €{{ $amt }}
                        </button>
                        @endforeach
                    </div>
                    <button @click="custom = true; amount = ''"
                            :class="custom ? 'bg-amber-600 text-white border-amber-500' : 'bg-gray-900/50 text-gray-300 border-gray-600 hover:border-amber-500'"
                            class="w-full mt-3 border rounded-xl py-3 text-sm font-medium transition">
                        {{ __('messages.donate_custom') }}
                    </button>
                    <div x-show="custom" x-collapse class="mt-3">
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg">€</span>
                            <input type="number" x-model="amount" min="1" step="0.01" placeholder="0.00"
                                   class="w-full bg-gray-900 border border-gray-600 rounded-xl pl-10 pr-4 py-3 text-white text-lg focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        </div>
                    </div>
                </div>

                {{-- PayPal Button --}}
                <form :action="'https://www.paypal.com/cgi-bin/webscr'" method="POST" target="_blank">
                    <input type="hidden" name="cmd" value="_donations">
                    <input type="hidden" name="business" value="{{ $paypalEmail }}">
                    <input type="hidden" name="item_name" value="Wolffiles.eu Donation">
                    <input type="hidden" name="currency_code" value="EUR">
                    <input type="hidden" name="amount" :value="amount">
                    <input type="hidden" name="return" value="{{ route('donate') }}?thanks=1">
                    <input type="hidden" name="cancel_return" value="{{ route('donate') }}">
                    <input type="hidden" name="notify_url" value="{{ route('donate.paypal.ipn') }}">
                    <input type="hidden" name="no_note" value="0">
                    <input type="hidden" name="no_shipping" value="1">

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white font-bold py-4 px-8 rounded-xl text-lg transition transform hover:scale-[1.02] shadow-lg shadow-amber-500/20">
                        <span class="flex items-center justify-center gap-3">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M7.076 21.337H2.47a.641.641 0 01-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797H9.603c-.564 0-1.04.408-1.13.964L7.076 21.337z"/></svg>
                            {{ __('messages.donate_via_paypal') }} — €<span x-text="amount || '...'"></span>
                        </span>
                    </button>
                </form>
            </div>
            @endif

            {{-- Thank You Message --}}
            @if(request('thanks'))
            <div class="bg-green-900/30 border border-green-700 rounded-2xl p-6 text-center">
                <div class="text-4xl mb-3">🎉</div>
                <h3 class="text-xl font-bold text-green-400">{{ __('messages.thank_you') }}</h3>
            </div>
            @endif
        </div>

        {{-- Sidebar (Right) --}}
        <div class="space-y-8">

            {{-- Top Supporters --}}
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">🏆 {{ __('messages.wall_of_fame') }}</h3>
                @forelse($topDonors as $donor)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-700/50' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">
                            @if($loop->index === 0) 🥇
                            @elseif($loop->index === 1) 🥈
                            @elseif($loop->index === 2) 🥉
                            @else <span class="text-gray-500 text-sm w-6 text-center">{{ $loop->index + 1 }}</span>
                            @endif
                        </span>
                        <div>
                            <div class="text-white text-sm font-medium">
                                @if($donor->user_id)
                                    {{ \App\Models\User::find($donor->user_id)?->name ?? $donor->name }}
                                @else
                                    {{ $donor->name }}
                                @endif
                            </div>
                            <div class="text-gray-500 text-xs">{{ $donor->count }} {{ __('messages.times_donated') }}</div>
                        </div>
                    </div>
                    <span class="text-amber-400 font-semibold">€{{ number_format($donor->total, 0) }}</span>
                </div>
                @empty
                <p class="text-gray-500 text-sm text-center py-4">{{ __('messages.no_donations_yet') }}</p>
                @endforelse
            </div>

            {{-- Recent Donations --}}
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">💝 {{ __('messages.recent_donors') }}</h3>
                @forelse($recentDonors->take(10) as $donation)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-700/50' : '' }}">
                    <div>
                        <div class="text-white text-sm">{{ $donation->display_name }}</div>
                        @if($donation->message)
                        <div class="text-gray-500 text-xs italic truncate max-w-[150px]">"{{ $donation->message }}"</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-amber-400 font-semibold text-sm">€{{ number_format($donation->amount, 2) }}</div>
                        <div class="text-gray-600 text-xs">{{ $donation->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-sm text-center py-4">{{ __('messages.no_donations_yet') }}</p>
                @endforelse
            </div>

            {{-- Monthly Costs --}}
            <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📊 {{ __('messages.monthly_costs') }}</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">🖥️ {{ __('messages.cost_servers') }}</span>
                        @php
                        $cServers = (float) \App\Models\DonationSetting::get("cost_servers", "25");
                        $cStorage = (float) \App\Models\DonationSetting::get("cost_storage", "15");
                        $cDomain = (float) \App\Models\DonationSetting::get("cost_domain", "5");
                        $cOther = (float) \App\Models\DonationSetting::get("cost_other", "5");
                        $cTotal = $cServers + $cStorage + $cDomain + $cOther;
                    @endphp
                    <span class="text-white">€{{ number_format($cServers, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">☁️ {{ __('messages.cost_storage') }}</span>
                        <span class="text-white">€{{ number_format($cStorage, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">🌐 {{ __('messages.cost_domain') }}</span>
                        <span class="text-white">€{{ number_format($cDomain, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">⚡ {{ __('messages.cost_other') }}</span>
                        <span class="text-white">€{{ number_format($cOther, 2) }}</span>
                    </div>
                    <div class="border-t border-gray-700 pt-2 flex justify-between font-semibold">
                        <span class="text-gray-300">Total</span>
                        <span class="text-amber-400">€{{ number_format($cTotal, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
