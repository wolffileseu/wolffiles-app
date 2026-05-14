@props([
    'title'       => null,
    'article'     => null,    // App\Models\WikiArticle (oder null bei Special-Pages)
    'activeTab'   => 'read',  // read | edit | history | talk
    'pageType'    => 'article', // article | talk
    'sidebarSlot' => null,    // optional extra sidebar items
])

<x-layouts.app :title="$title ?? 'Wiki'">
    {{-- No-Flash-of-Wrong-Theme: Inline script läuft VOR CSS-paint --}}
    <script>
        (function(){
            try {
                var t = localStorage.getItem('wolffiles-wiki-theme') || 'dark';
                if (t === 'light') {
                    document.documentElement.classList.add('wiki-pre-light');
                }
            } catch(e){}
        })();
    </script>
    <style>
        /* Pre-paint hint: wird vom JS sofort ersetzt sobald .wiki-skin im DOM ist */
        .wiki-pre-light .wiki-skin { background: #f8f9fa !important; }
    </style>

    @vite(['resources/css/wiki.css', 'resources/js/wiki-theme.js'])

    <div class="wiki-skin">
        <div class="wiki-container">
            <button class="wiki-mobile-toggle" type="button">☰ Menu</button>

            <x-wiki.tabs
                :article="$article"
                :active-tab="$activeTab"
                :page-type="$pageType" />

            <div class="wiki-shell">
            <aside class="wiki-sidebar">
                <x-wiki.sidebar :article="$article">
                    @isset($sidebarSlot){{ $sidebarSlot }}@endisset
                </x-wiki.sidebar>
            </aside>

            <main class="wiki-content">
                {{ $slot }}
            </main>
            </div>
        </div>
    </div>
</x-layouts.app>
