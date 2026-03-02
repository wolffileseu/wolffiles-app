<x-layouts.app title="My Favorites">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">My Favorites</h1>

        @if($favorites->isEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
                <p class="text-gray-400 text-lg mb-4">No favorites yet.</p>
                <a href="{{ route('files.index') }}" class="text-amber-400 hover:text-amber-300">Browse files →</a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($favorites as $favorite)
                    @if($favorite->file)
                        @include('components.file-card', ['file' => $favorite->file])
                    @endif
                @endforeach
            </div>
            <div class="mt-8">{{ $favorites->links() }}</div>
        @endif
    </div>
</x-layouts.app>
