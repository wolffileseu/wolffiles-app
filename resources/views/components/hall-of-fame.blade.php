{{-- Hall of Fame - Top Uploaders --}}
{{-- Include on homepage or statistics: @include('components.hall-of-fame') --}}
@php
    $topUploaders = \App\Models\User::withCount(['files as approved_uploads_count' => function($q) {
            $q->where('status', 'approved');
        }])
        ->having('approved_uploads_count', '>', 0)
        ->orderByDesc('approved_uploads_count')
        ->limit(10)
        ->get();

    $topDownloaded = \App\Models\File::where('status', 'approved')
        ->with('user')
        ->orderByDesc('download_count')
        ->limit(5)
        ->get();

    $topRated = \App\Models\File::where('status', 'approved')
        ->where('rating_count', '>=', 3)
        ->with('user')
        ->orderByDesc('average_rating')
        ->limit(5)
        ->get();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Top Uploaders --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider mb-4 flex items-center space-x-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <span>{{ __('messages.top_uploaders') }}</span>
        </h3>
        <div class="space-y-3">
            @foreach($topUploaders as $i => $uploader)
                <a href="{{ route('profile.show', $uploader) }}" class="flex items-center space-x-3 hover:bg-gray-700/50 rounded px-2 py-1.5 -mx-2 transition-colors">
                    <span class="w-6 text-center text-sm font-bold {{ $i < 3 ? 'text-amber-400' : 'text-gray-500' }}">{{ $i + 1 }}</span>
                    <img src="{{ $uploader->avatar_url }}" class="w-7 h-7 rounded-full" alt="">
                    <span class="text-sm text-gray-300 truncate flex-1">{{ $uploader->name }}</span>
                    <span class="text-xs text-gray-500">{{ $uploader->approved_uploads_count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Most Downloaded --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider mb-4 flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>{{ __('messages.most_downloaded') }}</span>
        </h3>
        <div class="space-y-3">
            @foreach($topDownloaded as $i => $file)
                <a href="{{ route('files.show', $file) }}" class="flex items-center space-x-3 hover:bg-gray-700/50 rounded px-2 py-1.5 -mx-2 transition-colors">
                    <span class="w-6 text-center text-sm font-bold {{ $i < 3 ? 'text-amber-400' : 'text-gray-500' }}">{{ $i + 1 }}</span>
                    <span class="text-sm text-gray-300 truncate flex-1">{{ $file->title }}</span>
                    <span class="text-xs text-gray-500">↓ {{ number_format($file->download_count) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Highest Rated --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider mb-4 flex items-center space-x-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <span>{{ __('messages.highest_rated') }}</span>
        </h3>
        <div class="space-y-3">
            @foreach($topRated as $i => $file)
                <a href="{{ route('files.show', $file) }}" class="flex items-center space-x-3 hover:bg-gray-700/50 rounded px-2 py-1.5 -mx-2 transition-colors">
                    <span class="w-6 text-center text-sm font-bold {{ $i < 3 ? 'text-amber-400' : 'text-gray-500' }}">{{ $i + 1 }}</span>
                    <span class="text-sm text-gray-300 truncate flex-1">{{ $file->title }}</span>
                    <span class="text-xs text-amber-400">★ {{ number_format($file->average_rating, 1) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
