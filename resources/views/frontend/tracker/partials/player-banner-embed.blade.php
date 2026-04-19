{{-- Player-Banner Embed-Box with 4 variants + iframe --}}
@php
    $bannerUrl = route('tracker.player.banner', $player);
    $embedUrl  = route('tracker.player.embed', $player);
    $playerUrl = route('tracker.player.show', $player);
    $altText   = $player->name_clean ?: 'Wolffiles ET Player';
    $variants  = [
        1 => 'Minimal',
        2 => '+ Fav',
        3 => '+ Now',
        4 => 'Full',
    ];
    $iframeW = 240;
    $iframeH = 340;
@endphp

<div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6"
     x-data="playerBannerEmbed({
        bannerUrl: {{ json_encode($bannerUrl) }},
        embedUrl: {{ json_encode($embedUrl) }},
        playerUrl: {{ json_encode($playerUrl) }},
        altText: {{ json_encode($altText) }},
        iframeW: {{ $iframeW }},
        iframeH: {{ $iframeH }}
     })">

    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
        {{ __('embed_banner') }}
    </h3>

    {{-- Variant Switcher (PNG) --}}
    <div class="flex flex-wrap gap-1 mb-4">
        @foreach ($variants as $v => $label)
            <button type="button"
                    @click="variant = {{ $v }}"
                    :class="variant === {{ $v }} ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                    class="text-xs px-3 py-1.5 rounded font-medium transition">
                {{ $v }} · {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- PNG preview (reactive) --}}
    <div class="flex justify-center mb-4">
        <img :src="bannerUrl + '?variant=' + variant + '&_=' + Date.now()"
             :alt="altText"
             width="560" height="95"
             class="rounded max-w-full h-auto" />
    </div>

    {{-- PNG copy codes (reactive) --}}
    <div class="space-y-1.5">
        <template x-for="code in codes" :key="code.key">
            <div class="flex items-center gap-2">
                <span class="w-24 flex-shrink-0 text-xs text-gray-500" x-text="code.label"></span>
                <input type="text" readonly
                       :value="code.value"
                       class="flex-1 min-w-0 bg-gray-900 border border-gray-700 rounded px-2 py-1 text-xs text-gray-300 font-mono focus:outline-none focus:border-yellow-600" />
                <button type="button"
                        @click="copy(code.key, code.value)"
                        class="flex-shrink-0 w-24 text-xs px-3 py-1 rounded font-medium transition"
                        :class="copied === code.key ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'">
                    <span x-show="copied !== code.key">{{ __('copy') }}</span>
                    <span x-show="copied === code.key" x-cloak>&check;</span>
                </button>
            </div>
        </template>
    </div>

    {{-- Divider --}}
    <div class="my-5 border-t border-gray-700"></div>

    {{-- Vertical iframe embed --}}
    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
        {{ __('vertical_banner') }}
    </h4>

    {{-- iframe variant switcher --}}
    <div class="flex flex-wrap gap-1 mb-4">
        @foreach ($variants as $v => $label)
            <button type="button"
                    @click="iframeVariant = {{ $v }}"
                    :class="iframeVariant === {{ $v }} ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                    class="text-xs px-3 py-1.5 rounded font-medium transition">
                {{ $v }} · {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="flex flex-col lg:flex-row gap-4 items-start">
        <div class="flex-shrink-0">
            <iframe :src="embedUrl + '?variant=' + iframeVariant"
                    :width="iframeW" :height="iframeH"
                    frameborder="0" scrolling="no"
                    style="border:0; border-radius: 4px; background: #1a1a1a;"
                    loading="lazy"
                    x-ref="previewFrame"
                    @load="resizeFrame()"></iframe>
        </div>

        <div class="flex-1 w-full min-w-0 space-y-3">
            <p class="text-xs text-gray-500">{{ __('vertical_banner_help') }}</p>
            <div class="flex items-start gap-2">
                <span class="w-24 flex-shrink-0 text-xs text-gray-500 pt-2">iframe</span>
                <textarea readonly
                          :value="iframeCode"
                          rows="4"
                          class="flex-1 min-w-0 bg-gray-900 border border-gray-700 rounded px-2 py-1 text-xs text-gray-300 font-mono focus:outline-none focus:border-yellow-600 resize-none"></textarea>
                <button type="button"
                        @click="copy('iframe', iframeCode)"
                        class="flex-shrink-0 w-24 text-xs px-3 py-1 rounded font-medium transition self-start"
                        :class="copied === 'iframe' ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'">
                    <span x-show="copied !== 'iframe'">{{ __('copy') }}</span>
                    <span x-show="copied === 'iframe'" x-cloak>&check;</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function playerBannerEmbed(config) {
    return {
        variant: 1,
        iframeVariant: 1,
        copied: null,
        bannerUrl: config.bannerUrl,
        embedUrl: config.embedUrl,
        playerUrl: config.playerUrl,
        altText: config.altText,
        iframeW: config.iframeW,
        iframeH: config.iframeH,

        get currentBanner() {
            return this.bannerUrl + '?variant=' + this.variant;
        },
        get codes() {
            const banner = this.currentBanner;
            const url = this.playerUrl;
            const alt = this.altText;
            return [
                { key: 'url',      label: '{{ __('direct_link') }}',  value: banner },
                { key: 'html',     label: 'HTML',     value: '<a href="' + url + '"><img src="' + banner + '" alt="' + alt + '"></a>' },
                { key: 'bbcode',   label: 'BBCode',   value: '[url=' + url + '][img]' + banner + '[/img][/url]' },
                { key: 'markdown', label: 'Markdown', value: '[![' + alt + '](' + banner + ')](' + url + ')' },
            ];
        },
        get iframeCode() {
            return '<iframe src="' + this.embedUrl + '?variant=' + this.iframeVariant + '" width="' + this.iframeW + '" height="' + this.iframeH + '" frameborder="0" scrolling="no" style="border:0;"></iframe>';
        },

        resizeFrame() {
            setTimeout(() => {
                const f = this.$refs.previewFrame;
                if (!f) return;
                try {
                    const doc = f.contentDocument || f.contentWindow?.document;
                    if (doc && doc.body) {
                        const h = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
                        if (h > 100) f.style.height = (h + 4) + 'px';
                    }
                } catch (e) { /* cross-origin ok */ }
            }, 200);
        },

        copy(key, text) {
            try { navigator.clipboard.writeText(text); }
            catch (e) {
                const tmp = document.createElement('textarea');
                tmp.value = text;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                document.body.removeChild(tmp);
            }
            this.copied = key;
            setTimeout(() => { if (this.copied === key) this.copied = null; }, 1500);
        }
    }
}
</script>
