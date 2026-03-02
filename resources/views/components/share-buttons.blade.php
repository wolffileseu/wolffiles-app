{{-- Share Buttons Component --}}
{{-- Usage: @include('components.share-buttons', ['url' => $url, 'title' => $title]) --}}
@php
    $shareUrl = urlencode($url ?? request()->url());
    $shareTitle = urlencode($title ?? '');
@endphp

<div class="flex items-center space-x-2">
    <span class="text-gray-500 text-sm">{{ __('messages.share') }}:</span>

    {{-- Twitter/X --}}
    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 flex items-center justify-center rounded bg-gray-700 hover:bg-gray-600 text-gray-400 hover:text-white transition-colors"
       title="Share on X">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
    </a>

    {{-- Reddit --}}
    <a href="https://reddit.com/submit?url={{ $shareUrl }}&title={{ $shareTitle }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 flex items-center justify-center rounded bg-gray-700 hover:bg-orange-600 text-gray-400 hover:text-white transition-colors"
       title="Share on Reddit">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>
    </a>

    {{-- Discord --}}
    <a href="https://discord.com/channels/@me"
       onclick="navigator.clipboard.writeText('{{ $url ?? request()->url() }}'); this.title='Link copied!'; return true;"
       class="w-8 h-8 flex items-center justify-center rounded bg-gray-700 hover:bg-indigo-600 text-gray-400 hover:text-white transition-colors"
       title="Copy for Discord">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286z"/></svg>
    </a>

    {{-- Copy Link --}}
    <button onclick="navigator.clipboard.writeText('{{ $url ?? request()->url() }}'); this.querySelector('span').textContent='{{ __('messages.copied') }}!'; setTimeout(() => this.querySelector('span').textContent='{{ __('messages.copy_link') }}', 2000)"
            class="flex items-center space-x-1 px-3 py-1.5 rounded bg-gray-700 hover:bg-gray-600 text-gray-400 hover:text-white text-xs transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span>{{ __('messages.copy_link') }}</span>
    </button>
</div>

{{-- Embed Code (collapsible) --}}
<div x-data="{ showEmbed: false }" class="mt-3">
    <button @click="showEmbed = !showEmbed" class="text-xs text-gray-500 hover:text-gray-300 transition-colors">
        {{ __('messages.embed_code') }} ▾
    </button>
    <div x-show="showEmbed" x-cloak class="mt-2">
        <input type="text" readonly
               value='<iframe src="{{ $url ?? request()->url() }}?embed=1" width="400" height="200" frameborder="0"></iframe>'
               class="w-full bg-gray-700 border-gray-600 text-gray-300 rounded px-3 py-2 text-xs font-mono"
               onclick="this.select(); navigator.clipboard.writeText(this.value);">
    </div>
</div>
