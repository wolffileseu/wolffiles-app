@props(['dismissible' => true])

<div id="enhanced-tracker-banner" 
     class="relative mb-6 rounded-lg border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent p-5 shadow-sm"
     style="display: none;">

    @if($dismissible)
    <button onclick="dismissEnhancedBanner()" 
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-200 transition"
            title="{{ __('Hide this notice') }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
    @endif

    <div class="flex items-start gap-4">
        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        
        <div class="flex-1 min-w-0">
            <h3 class="text-base font-semibold text-white">
                {{ __('Enhanced Tracker — for server admins') }}
            </h3>
            <p class="mt-1 text-sm text-gray-300">
                {{ __('Get detailed stats for your ET:Legacy server — match history, weapon accuracy, headshots, damage. Your server reports to the official ET:Legacy tracker (Trackbase) and Wolffiles in parallel — both systems receive all data.') }}
            </p>
            
            <div class="mt-3 flex items-stretch gap-2 max-w-xl">
                <code id="enhanced-banner-code" class="flex-1 rounded bg-gray-900 text-emerald-300 px-3 py-2 font-mono text-xs sm:text-sm truncate">set sv_tracker "et-tracker.trackbase.net:4444 tracker.wolffiles.eu:4444"</code>
                <button id="enhanced-banner-copy-btn"
                        onclick="copyEnhancedBannerCode()"
                        class="flex-shrink-0 rounded bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm font-medium transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ __('Copy') }}</span>
                </button>
            </div>

            <p class="mt-3 text-xs text-gray-400">
                {{ __('Requires multi-tracker support in your ET:Legacy build —') }} <code class="font-mono">sv_tracker</code> {{ __('accepts a space-separated list of endpoints.') }}
                <a href="https://github.com/etlegacy/etlegacy/wiki/Changelog#server" target="_blank" rel="noopener" 
                   class="text-emerald-400 hover:underline">
                    {{ __('See changelog') }} →
                </a>
            </p>
            <p class="mt-2 text-xs text-amber-400/90">
                {{ __('Also requires') }} <code class="font-mono">sv_advert "3"</code> {{ __('so statistics are sent at all.') }}
            </p>
        </div>
    </div>
</div>

<script>
(function() {
    const banner = document.getElementById('enhanced-tracker-banner');
    if (!banner) return;
    
    // Show only if not dismissed
    try {
        if (localStorage.getItem('enhanced_banner_dismissed') !== '1') {
            banner.style.display = '';
        }
    } catch (e) {
        banner.style.display = '';
    }
})();

function dismissEnhancedBanner() {
    try { localStorage.setItem('enhanced_banner_dismissed', '1'); } catch (e) {}
    const b = document.getElementById('enhanced-tracker-banner');
    if (b) b.style.display = 'none';
}

function copyEnhancedBannerCode() {
    const text = 'set sv_tracker "et-tracker.trackbase.net:4444 tracker.wolffiles.eu:4444"';
    const btn = document.getElementById('enhanced-banner-copy-btn');
    const span = btn.querySelector('span');
    const original = span.textContent;
    
    (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject())
        .catch(() => {
            // Fallback for older browsers
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        })
        .finally(() => {
            span.textContent = '{{ __('Copied!') }}';
            btn.classList.add('bg-emerald-700');
            setTimeout(() => {
                span.textContent = original;
                btn.classList.remove('bg-emerald-700');
            }, 2000);
        });
}
</script>
