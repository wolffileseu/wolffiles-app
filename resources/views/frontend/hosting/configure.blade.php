<x-layouts.app>
<div class="max-w-4xl mx-auto px-4 py-8" x-data="serverConfigurator()">

    <a href="{{ route('hosting.index') }}" class="text-amber-500 hover:text-amber-400 text-sm mb-4 inline-block">{{ __('messages.hosting_back_to_overview') }}</a>

    <h1 class="text-3xl font-bold mb-2">{{ $product->name }} {{ __('messages.hosting_configure') }}</h1>
    <p class="text-gray-400 mb-8">{{ $product->description }}</p>

    <form action="{{ route('hosting.checkout') }}" method="POST">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        <div class="grid md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-6">

                {{-- Slots Slider --}}
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-300 mb-3">{{ __('messages.hosting_player_slots') }}</label>
                    <div class="flex items-center gap-4 mb-3">
                        <button type="button" @click="slots = Math.max({{ $product->min_slots }}, slots - 2)"
                                class="w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center justify-center text-xl font-bold">−</button>
                        <input type="range" name="slots" x-model.number="slots"
                               min="{{ $product->min_slots }}" max="{{ $product->max_slots }}" step="2"
                               class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500">
                        <button type="button" @click="slots = Math.min({{ $product->max_slots }}, slots + 2)"
                                class="w-10 h-10 bg-gray-700 hover:bg-gray-600 rounded-lg flex items-center justify-center text-xl font-bold">+</button>
                    </div>
                    <div class="text-center">
                        <span class="text-4xl font-bold text-amber-500" x-text="slots"></span>
                        <span class="text-gray-400 ml-1">{{ __('messages.hosting_slots') }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>{{ __('messages.hosting_min') }}: {{ $product->min_slots }}</span>
                        <span>{{ __('messages.hosting_max') }}: {{ $product->max_slots }}</span>
                    </div>
                </div>

                {{-- Server Name --}}
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-300 mb-2">{{ __('messages.hosting_server_name') }}</label>
                    <input type="text" name="server_name" required maxlength="64"
                           placeholder="{{ __('messages.hosting_server_name_placeholder') }}"
                           value="{{ old('server_name') }}"
                           class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                </div>

                {{-- Mod Selection --}}
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-300 mb-3">{{ __('messages.hosting_mod') }}</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @php
                            $mods = match($product->game) {
                                'etl' => ['etmain' => 'Legacy Mod', 'jaymod' => 'jaymod', 'nitmod' => 'N!tmod', 'noquarter' => 'NoQuarter', 'silent' => 'Silent Mod', 'etpro' => 'ETPro'],
                                'et' => ['etmain' => 'Vanilla', 'etpro' => 'ETPro', 'jaymod' => 'jaymod', 'nitmod' => 'N!tmod', 'noquarter' => 'NoQuarter', 'silent' => 'Silent Mod'],
                                'rtcw' => ['main' => 'Vanilla', 'osp' => 'OSP', 'shrub' => 'Shrub'],
                                default => ['etmain' => 'Vanilla'],
                            };
                        @endphp
                        @foreach($mods as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="mod" value="{{ $value }}" {{ $loop->first ? 'checked' : '' }}
                                   class="peer sr-only" x-model="mod">
                            <div class="border border-gray-600 peer-checked:border-amber-500 peer-checked:bg-amber-500/10 rounded-lg px-3 py-2 text-center text-sm text-gray-300 peer-checked:text-amber-400 hover:border-gray-500 transition-colors">
                                {{ $label }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Duration --}}
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-300 mb-3">{{ __('messages.hosting_duration') }}</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach(['daily' => __('messages.hosting_1_day'), 'weekly' => __('messages.hosting_7_days'), 'monthly' => __('messages.hosting_30_days'), 'quarterly' => __('messages.hosting_90_days')] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="period" value="{{ $value }}" {{ $value === 'monthly' ? 'checked' : '' }}
                                   class="peer sr-only" x-model="period">
                            <div class="border border-gray-600 peer-checked:border-amber-500 peer-checked:bg-amber-500/10 rounded-lg px-3 py-3 text-center transition-colors hover:border-gray-500">
                                <div class="text-sm font-semibold" :class="period === '{{ $value }}' ? 'text-amber-400' : 'text-gray-300'">{{ $label }}</div>
                                <div class="text-xs text-gray-500 mt-1" x-text="formatPrice(prices.{{ $value }}) + '€'"></div>
                                @if($value === 'quarterly')
                                    <span class="text-[10px] text-green-400 font-bold">{{ __('messages.hosting_save_20') }}</span>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Advanced Settings --}}
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6" x-data="{ showAdvanced: false }">
                    <button type="button" @click="showAdvanced = !showAdvanced"
                            class="flex items-center gap-2 text-sm text-gray-400 hover:text-white">
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': showAdvanced }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        {{ __('messages.hosting_advanced_settings') }}
                    </button>
                    <div x-show="showAdvanced" x-collapse class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">{{ __('messages.hosting_rcon_password') }} ({{ __('messages.hosting_rcon_auto') }})</label>
                            <input type="text" name="rcon_password" maxlength="32"
                                   class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500"
                                   placeholder="{{ __('messages.hosting_rcon_auto') }}">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">{{ __('messages.hosting_server_password') }}</label>
                            <input type="text" name="server_password" maxlength="32"
                                   class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500"
                                   placeholder="{{ __('messages.hosting_server_password_hint') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Price Summary --}}
            <div class="md:col-span-1">
                <div class="sticky top-24 bg-gray-800/80 border border-gray-700 rounded-xl p-6">
                    <h3 class="font-bold text-lg mb-4">{{ __('messages.hosting_summary') }}</h3>
                    <div class="space-y-3 text-sm mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('messages.hosting_game') }}</span>
                            <span class="text-white">{{ $product->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('messages.hosting_slots') }}</span>
                            <span class="text-white font-bold" x-text="slots"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('messages.hosting_mod') }}</span>
                            <span class="text-white" x-text="modName()"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('messages.hosting_duration') }}</span>
                            <span class="text-white" x-text="periodName()"></span>
                        </div>
                        <hr class="border-gray-700">
                        <div class="flex justify-between">
                            <span class="text-gray-400">RAM</span>
                            <span class="text-white" x-text="resources.memory_mb + ' MB'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Disk</span>
                            <span class="text-white" x-text="(resources.disk_mb / 1024).toFixed(1) + ' GB'"></span>
                        </div>
                    </div>
                    <div class="border-t border-gray-700 pt-4 mb-6">
                        <div class="flex justify-between items-baseline">
                            <span class="text-gray-400">{{ __('messages.hosting_total') }}</span>
                            <span class="text-3xl font-bold text-amber-500" x-text="formatPrice(currentPrice()) + '€'"></span>
                        </div>
                    </div>
                    @auth
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl transition-colors text-center">
                            {{ __('messages.hosting_order_now') }} →
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-2">{{ __('messages.hosting_continue_to_payment') }}</p>
                    @else
                        <a href="{{ route('login') }}" class="block w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl transition-colors text-center">
                            {{ __('messages.hosting_login_to_order') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function serverConfigurator() {
    return {
        slots: {{ $product->slots }},
        period: 'monthly',
        mod: '{{ $product->game === "rtcw" ? "main" : "etmain" }}',
        prices: @json($product->calculatePrices($product->slots)),
        resources: @json($product->calculateResources($product->slots)),
        init() { this.$watch('slots', () => this.recalculate()); },
        async recalculate() {
            try {
                const res = await fetch('{{ route("hosting.calculate-price") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ product_id: {{ $product->id }}, slots: this.slots }),
                });
                const data = await res.json();
                this.prices = data.prices;
                this.resources = data.resources;
                this.slots = data.slots;
            } catch (e) { console.error(e); }
        },
        currentPrice() { return this.prices[this.period] || this.prices.monthly; },
        formatPrice(price) { return parseFloat(price).toFixed(2); },
        modName() {
            const names = { etmain: 'Vanilla/Legacy', etpro: 'ETPro', jaymod: 'jaymod', nitmod: 'N!tmod', noquarter: 'NoQuarter', silent: 'Silent Mod', main: 'Vanilla', osp: 'OSP', shrub: 'Shrub' };
            return names[this.mod] || this.mod;
        },
        periodName() {
            const names = { daily: '{{ __("messages.hosting_1_day") }}', weekly: '{{ __("messages.hosting_7_days") }}', monthly: '{{ __("messages.hosting_30_days") }}', quarterly: '{{ __("messages.hosting_90_days") }}' };
            return names[this.period] || this.period;
        },
    }
}
</script>
</x-layouts.app>
