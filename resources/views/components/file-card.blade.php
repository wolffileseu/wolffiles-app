<a href="{{ route('files.show', $file) }}" class="block bg-gray-800 rounded-lg border border-gray-700 overflow-hidden hover:border-amber-600 transition-colors group">
    {{-- Thumbnail --}}
    <div class="aspect-video bg-gray-700 overflow-hidden">
        @if($file->thumbnail_url)
            <img src="{{ $file->thumbnail_url }}" alt="{{ $file->display_title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" width="320" height="180">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-500">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path>
                </svg>
            </div>
        @endif
    </div>

    <div class="p-4">
        <h3 class="text-white font-semibold text-sm mb-1 truncate group-hover:text-amber-400 transition-colors">
            {{ $file->display_title }}
        </h3>

        <div class="flex items-center justify-between text-xs text-gray-400 mb-2">
            <span class="bg-gray-700 px-2 py-0.5 rounded">{{ $file->category?->name }}</span>
            @if($file->game)
                <span>{{ $file->game }}</span>
            @endif
        </div>

        {{-- Tags --}}
        @if($file->tags->isNotEmpty())
            <div class="flex flex-wrap gap-1 mb-2">
                @foreach($file->tags->take(3) as $tag)
                    <span class="bg-amber-600/15 text-amber-400 px-1.5 py-0.5 rounded text-[10px] leading-tight">{{ $tag->name }}</span>
                @endforeach
                @if($file->tags->count() > 3)
                    <span class="text-gray-500 text-[10px] leading-tight py-0.5">+{{ $file->tags->count() - 3 }}</span>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between text-xs text-gray-500">
            <span>{{ $file->file_size_formatted }}</span>
            <span>↓ {{ number_format($file->download_count) }}</span>
            @if($file->average_rating > 0)
                <span class="text-amber-400">★ {{ number_format($file->average_rating, 1) }}</span>
            @endif
        </div>
    </div>
</a>
