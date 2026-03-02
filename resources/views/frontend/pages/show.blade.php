<x-layouts.app :title="$title" :seo="$seo ?? []">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('messages.home') }}</a> /
            <span class="text-gray-300">{{ $title }}</span>
        </nav>

        <h1 class="text-3xl font-bold text-white mb-6">{{ $title }}</h1>

        {{-- Content --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
            @if(($page->content_type ?? 'richtext') === 'html')
                {{-- Raw HTML content --}}
                {!! $content !!}
            @elseif(($page->content_type ?? 'richtext') === 'markdown')
                {{-- Markdown content --}}
                <div class="prose prose-invert max-w-none">
                    {!! Str::markdown($content ?? '') !!}
                </div>
            @else
                {{-- Rich text / auto-detect markdown --}}
                @php
                    $looksLikeMarkdown = $content && (str_starts_with(trim($content), "#") || str_contains($content, "##") || str_contains($content, "**"));
                @endphp
                @if($looksLikeMarkdown)
                    <div class="prose prose-invert max-w-none break-words" style="overflow-wrap: anywhere;">
                        {!! Str::markdown($content ?? "") !!}
                    </div>
                @else
                    <div class="prose prose-invert max-w-none break-words" style="overflow-wrap: anywhere;">
                        {!! $content !!}
                    </div>
                @endif
            @endif
        </div>

        {{-- PDF Attachment --}}
        @if($page->pdf_path)
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zm-3 9.5c0 .83-.67 1.5-1.5 1.5H7v2H5.5v-6H8.5c.83 0 1.5.67 1.5 1.5v1zm5 3c0 .83-.67 1.5-1.5 1.5H11v-6h2.5c.83 0 1.5.67 1.5 1.5v3zm4-3c0 .28-.22.5-.5.5H17v1h1.5v1H17v2h-1.5v-6H19.5c.28 0 .5.22.5.5v1z"/>
                        </svg>
                        <div>
                            <p class="text-white font-medium">PDF Dokument</p>
                            <p class="text-gray-400 text-sm">Anhang herunterladen</p>
                        </div>
                    </div>
                    <a href="{{ Storage::disk('s3')->url($page->pdf_path) }}"
                       target="_blank"
                       class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        ↓ PDF öffnen
                    </a>
                </div>
            </div>
        @endif

        {{-- Last updated --}}
        <div class="text-gray-500 text-xs mt-4">
            {{ __('messages.last_updated') ?? 'Zuletzt aktualisiert' }}: {{ $page->updated_at->format('d.m.Y H:i') }}
        </div>
    </div>
</x-layouts.app>
