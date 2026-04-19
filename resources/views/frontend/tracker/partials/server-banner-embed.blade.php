{{-- Server-Banner Embed-Box --}}
@php
    $bannerUrl = route('tracker.server.banner', $server);
    $embedUrl  = route('tracker.server.embed', $server);
    $serverUrl = route('tracker.server.show', $server);
    $altText   = $server->hostname_clean ?: 'Wolffiles ET Server';
    $iframeW = 240; $iframeH = 640;
    $iframeCode = '<iframe src="'.$embedUrl.'" width="'.$iframeW.'" height="'.$iframeH.'" frameborder="0" scrolling="no" style="border:0;"></iframe>';
    $pngCodes = [
        ['label' => __('messages.direct_link'), 'value' => $bannerUrl, 'key' => 'url'],
        ['label' => 'HTML',     'value' => '<a href="'.$serverUrl.'"><img src="'.$bannerUrl.'" alt="'.e($altText).'"></a>', 'key' => 'html'],
        ['label' => 'BBCode',   'value' => '[url='.$serverUrl.'][img]'.$bannerUrl.'[/img][/url]', 'key' => 'bbcode'],
        ['label' => 'Markdown', 'value' => '[!['.$altText.']('.$bannerUrl.')]('.$serverUrl.')', 'key' => 'markdown'],
    ];
@endphp

<div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6" x-data="bannerEmbed()">
    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
        {{ __('messages.embed_banner') }}
    </h3>

    {{-- PNG Banner preview --}}
    <div class="flex justify-center mb-4">
        <img src="{{ $bannerUrl }}?_={{ time() }}"
             alt="{{ $altText }}"
             width="560" height="95"
             class="rounded max-w-full h-auto" />
    </div>

    <div class="space-y-1.5">
        @foreach ($pngCodes as $c)
            <div class="flex items-center gap-2">
                <span class="w-24 flex-shrink-0 text-xs text-gray-500">{{ $c['label'] }}</span>
                <input type="text" readonly
                       x-ref="{{ $c['key'] }}"
                       value="{{ $c['value'] }}"
                       class="flex-1 min-w-0 bg-gray-900 border border-gray-700 rounded px-2 py-1 text-xs text-gray-300 font-mono focus:outline-none focus:border-yellow-600" />
                <button type="button"
                        @click="copy('{{ $c['key'] }}')"
                        class="flex-shrink-0 w-24 text-xs px-3 py-1 rounded font-medium transition"
                        :class="copied === '{{ $c['key'] }}' ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'">
                    <span x-show="copied !== '{{ $c['key'] }}'">{{ __('messages.copy') }}</span>
                    <span x-show="copied === '{{ $c['key'] }}'" x-cloak>✓</span>
                </button>
            </div>
        @endforeach
    </div>

    {{-- Divider --}}
    <div class="my-5 border-t border-gray-700"></div>

    {{-- Vertical iframe banner --}}
    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
        {{ __('messages.vertical_banner') }}
    </h4>

    <div class="flex flex-col lg:flex-row gap-4 items-start">
        {{-- Live iframe preview --}}
        <div class="flex-shrink-0">
            <iframe src="{{ $embedUrl }}"
                    width="{{ $iframeW }}" height="{{ $iframeH }}"
                    frameborder="0" scrolling="no"
                    style="border:0; border-radius: 4px; background: #1a1a1a;"
                    loading="lazy"
                    x-ref="previewFrame"
                    @load="resizeFrame()"></iframe>
        </div>

        {{-- iframe code --}}
        <div class="flex-1 w-full min-w-0 space-y-3">
            <p class="text-xs text-gray-500">{{ __('messages.vertical_banner_help') }}</p>

            <div class="flex items-start gap-2">
                <span class="w-24 flex-shrink-0 text-xs text-gray-500 pt-2">iframe</span>
                <textarea readonly
                          x-ref="iframe"
                          rows="4"
                          class="flex-1 min-w-0 bg-gray-900 border border-gray-700 rounded px-2 py-1 text-xs text-gray-300 font-mono focus:outline-none focus:border-yellow-600 resize-none">{{ $iframeCode }}</textarea>
                <button type="button"
                        @click="copy('iframe')"
                        class="flex-shrink-0 w-24 text-xs px-3 py-1 rounded font-medium transition self-start"
                        :class="copied === 'iframe' ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'">
                    <span x-show="copied !== 'iframe'">{{ __('messages.copy') }}</span>
                    <span x-show="copied === 'iframe'" x-cloak>✓</span>
                </button>
            </div>

            <div class="text-xs text-gray-500 space-y-0.5">
                <div><span class="text-gray-400">{{ __('messages.direct_link') }}:</span> <a href="{{ $embedUrl }}" target="_blank" class="text-yellow-500 hover:text-yellow-400">{{ $embedUrl }}</a></div>
            </div>
        </div>
    </div>
</div>

<script>
function bannerEmbed() {
    return {
        copied: null,
        resizeFrame() {
            // Give iframe content time to render, then measure + resize
            setTimeout(() => {
                const f = this.$refs.previewFrame;
                if (!f) return;
                try {
                    const doc = f.contentDocument || f.contentWindow?.document;
                    if (doc && doc.body) {
                        const h = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
                        if (h > 100) f.style.height = (h + 4) + 'px';
                    }
                } catch(e) { /* cross-origin, fine — default height is used */ }
            }, 200);
        },
        copy(key) {
            const input = this.$refs[key];
            input.select();
            input.setSelectionRange(0, 99999);
            try { navigator.clipboard.writeText(input.value); }
            catch(e) { document.execCommand('copy'); }
            this.copied = key;
            setTimeout(() => { if (this.copied === key) this.copied = null; }, 1500);
        }
    }
}
</script>
