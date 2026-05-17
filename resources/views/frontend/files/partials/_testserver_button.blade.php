{{-- Test Server Sidebar Button (NUR für ET-Maps + Feature aktiv) --}}
@php
    $tsEnabled = false;
    $tsServerCount = 0;
    $tsDefaultMinutes = 20;

    try {
        $tsSettings = \App\Models\TestserverSetting::current();
        if ($tsSettings->feature_enabled && $tsSettings->public_visible) {
            $tsServerCount = \App\Models\Testserver::public()->count();
            $tsEnabled = $tsServerCount > 0;
        }
        $tsDefaultMinutes = $tsSettings->default_session_minutes ?? 20;
    } catch (\Throwable $e) {
        $tsEnabled = false;
    }

    // NUR ET-Maps: Category-Slug 'maps' UND Parent-Slug 'et'
    // (excludes: rtcw-maps-mp, et-domination-maps, et-quake-wars-maps, etfortress-maps, etc.)
    $isEtMap = $file->category?->slug === 'maps'
            && $file->category?->parent?->slug === 'et';
@endphp

@if($isEtMap && $tsEnabled)
    <div class="bg-gradient-to-br from-emerald-900/30 to-emerald-900/10
                rounded-lg border border-emerald-700/40 p-5">
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                <span class="text-xl">🎮</span>
            </div>
            <div>
                <h3 class="font-bold text-white text-sm">
                    {{ __('testserver.sidebar_test_map_button') }}
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ __('testserver.sidebar_test_map_hint', ['min' => $tsDefaultMinutes]) }}
                </p>
            </div>
        </div>

        <a href="{{ route('testserver.launch', ['map' => $file->slug]) }}"
           class="block w-full bg-emerald-600 hover:bg-emerald-500 text-white text-center
                  py-2.5 rounded-lg font-semibold text-sm transition-colors">
            🚀 {{ __('testserver.sidebar_test_map_button') }}
        </a>
    </div>
@endif
