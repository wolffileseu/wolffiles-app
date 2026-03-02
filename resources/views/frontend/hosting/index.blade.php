<x-layouts.app>
<div class="max-w-6xl mx-auto px-4 py-8">

    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
            🖥️ <span class="text-amber-500">{{ __('messages.hosting_title') }}</span>
        </h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto">
            {{ __('messages.hosting_subtitle') }} — {{ __('messages.hosting_from_price') }} <span class="text-amber-400 font-bold">0,50€/{{ __('messages.hosting_per_slot_month') }}</span>.
        </p>
    </div>

    {{-- USP Badges --}}
    <div class="flex flex-wrap justify-center gap-3 mb-12">
        @foreach([
            '⚡ ' . __('messages.hosting_instantly_online'),
            '🛡️ ' . __('messages.hosting_ddos_protection'),
            '📥 ' . __('messages.hosting_fastdl_included'),
            '🎮 ' . __('messages.hosting_all_mods'),
            '🔧 ' . __('messages.hosting_web_panel'),
            '💾 ' . __('messages.hosting_daily_backups'),
        ] as $usp)
            <span class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-full text-sm text-gray-300">{{ $usp }}</span>
        @endforeach
    </div>

    {{-- Products --}}
    <div class="grid md:grid-cols-3 gap-6 mb-16">
        @foreach($products as $product)
        <div class="bg-gray-800/50 border border-gray-700 rounded-2xl p-6 hover:border-amber-500/50 transition-all group">
            <div class="flex items-center gap-3 mb-4">
                <span class="text-3xl">
                    @if($product->game === 'etl') 🎮
                    @elseif($product->game === 'et') 🕹️
                    @else 🏰
                    @endif
                </span>
                <div>
                    <h2 class="text-xl font-bold text-white">{{ $product->name }}</h2>
                    <span class="text-xs text-gray-500">{{ $product->min_slots }}–{{ $product->max_slots }} {{ __('messages.hosting_slots') }}</span>
                </div>
            </div>

            <p class="text-gray-400 text-sm mb-6">{{ $product->description }}</p>

            <div class="mb-6">
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold text-amber-500">{{ number_format($product->price_per_slot_monthly, 2) }}€</span>
                    <span class="text-gray-500 text-sm">/ {{ __('messages.hosting_per_slot_month') }}</span>
                </div>
                <p class="text-gray-500 text-xs mt-1">
                    {{ __('messages.hosting_example_price') }} {{ $product->slots }} {{ __('messages.hosting_slots') }} = {{ number_format($product->calculatePrice($product->slots, 'monthly'), 2) }}€/{{ __('messages.hosting_per_month') }}
                </p>
            </div>

            <ul class="space-y-2 mb-6">
                @foreach($product->features ?? [] as $feature)
                <li class="flex items-center gap-2 text-sm text-gray-300">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            <a href="{{ route('hosting.configure', $product) }}"
               class="block w-full text-center bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl transition-colors group-hover:bg-amber-500">
                {{ __('messages.hosting_configure_server') }} →
            </a>
        </div>
        @endforeach
    </div>

    {{-- Why Wolffiles --}}
    <div class="bg-gray-800/30 border border-gray-700 rounded-2xl p-8 mb-12">
        <h2 class="text-2xl font-bold text-center mb-8">{{ __('messages.hosting_why_title') }}</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <span class="text-4xl block mb-3">📦</span>
                <h3 class="font-bold text-lg mb-2 text-white">{{ __('messages.hosting_why_integration_title') }}</h3>
                <p class="text-gray-400 text-sm">{{ __('messages.hosting_why_integration_desc') }}</p>
            </div>
            <div class="text-center">
                <span class="text-4xl block mb-3">📡</span>
                <h3 class="font-bold text-lg mb-2 text-white">{{ __('messages.hosting_why_tracker_title') }}</h3>
                <p class="text-gray-400 text-sm">{{ __('messages.hosting_why_tracker_desc') }}</p>
            </div>
            <div class="text-center">
                <span class="text-4xl block mb-3">🚀</span>
                <h3 class="font-bold text-lg mb-2 text-white">{{ __('messages.hosting_why_fastdl_title') }}</h3>
                <p class="text-gray-400 text-sm">{{ __('messages.hosting_why_fastdl_desc') }}</p>
            </div>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold text-center mb-8">{{ __('messages.hosting_faq_title') }}</h2>
        <div class="space-y-4" x-data="{ open: null }">
            @foreach([
                [__('messages.hosting_faq_speed_q'), __('messages.hosting_faq_speed_a')],
                [__('messages.hosting_faq_mods_q'), __('messages.hosting_faq_mods_a')],
                [__('messages.hosting_faq_ddos_q'), __('messages.hosting_faq_ddos_a')],
                [__('messages.hosting_faq_upgrade_q'), __('messages.hosting_faq_upgrade_a')],
                [__('messages.hosting_faq_expire_q'), __('messages.hosting_faq_expire_a')],
                [__('messages.hosting_faq_test_q'), __('messages.hosting_faq_test_a')],
            ] as $i => $faq)
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl overflow-hidden">
                <button @click="open = open === {{ $i }} ? null : {{ $i }}"
                        class="w-full flex items-center justify-between p-4 text-left">
                    <span class="font-semibold text-white">{{ $faq[0] }}</span>
                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open === {{ $i }} }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse>
                    <p class="px-4 pb-4 text-gray-400 text-sm">{{ $faq[1] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
</x-layouts.app>
