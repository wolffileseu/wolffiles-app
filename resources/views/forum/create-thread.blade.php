<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-8">

        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('forum.index') }}" class="hover:text-white transition">{{ __('messages.forum_title') }}</a>
            <span class="mx-2">›</span>
            @if($category->parent)
                <a href="{{ route('forum.category', $category->parent) }}" class="hover:text-white transition">
                    {{ $category->parent->translated_name }}
                </a>
                <span class="mx-2">›</span>
            @endif
            <a href="{{ route('forum.category', $category) }}" class="hover:text-white transition">
                {{ $category->translated_name }}
            </a>
            <span class="mx-2">›</span>
            <span class="text-white">{{ __('messages.forum_new_thread') }}</span>
        </nav>

        <h1 class="text-2xl font-bold text-white mb-6">{{ __('messages.forum_new_thread_in', ['name' => $category->translated_name]) }}</h1>

        <form method="POST" action="{{ route('forum.store-thread', $category) }}"
              class="bg-gray-800/50 border border-gray-700 rounded-lg p-6 space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.forum_thread_title') }}</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                       class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                       placeholder="{{ __('messages.forum_thread_title_placeholder') }}" required minlength="5" maxlength="255">
                @error('title')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.forum_thread_body') }}</label>
                @include('forum.partials.bbcode-toolbar')
                @error('body')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('forum.category', $category) }}"
                   class="text-gray-400 hover:text-white transition">
                    {{ __('messages.forum_cancel') }}
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-medium">
                    <i class="fas fa-paper-plane mr-2"></i>{{ __('messages.forum_create_thread') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
