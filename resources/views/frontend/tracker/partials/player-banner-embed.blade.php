{{-- Player-Banner Embed-Box --}}
@php
    $bannerUrl = route('tracker.player.banner', $player);
    $playerUrl = route('tracker.player.show', $player);
    $altText   = $player->name_clean ?: 'Wolffiles ET Player';
    $pngCodes = [
        ['label' => __('direct_link'), 'value' => $bannerUrl, 'key' => 'url'],
        ['label' => 'HTML',     'value' => '<a href="'.$playerUrl.'"><img src="'.$bannerUrl.'" alt="'.e($altText).'"></a>', 'key' => 'html'],
        ['label' => 'BBCode',   'value' => '[url='.$playerUrl.'][img]'.$bannerUrl.'[/img][/url]', 'key' => 'bbcode'],
        ['label' => 'Markdown', 'value' => '[!['.$altText.']('.$bannerUrl.')]('.$playerUrl.')', 'key' => 'markdown'],
    ];
@endphp

<div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6" x-data="playerBannerEmbed()">
    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
        {{ __('embed_banner') }}
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
                    <span x-show="copied !== '{{ $c['key'] }}'">{{ __('copy') }}</span>
                    <span x-show="copied === '{{ $c['key'] }}'" x-cloak>&check;</span>
                </button>
            </div>
        @endforeach
    </div>
</div>

<script>
function playerBannerEmbed() {
    return {
        copied: null,
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
