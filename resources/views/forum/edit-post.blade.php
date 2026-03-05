<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-8">

        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('forum.index') }}" class="hover:text-white transition">{{ __('messages.forum_title') }}</a>
            <span class="mx-2">›</span>
            <a href="{{ route('forum.category', $post->thread->category) }}" class="hover:text-white transition">
                {{ $post->thread->category->name }}
            </a>
            <span class="mx-2">›</span>
            <a href="{{ route('forum.thread', [$post->thread->category, $post->thread]) }}" class="hover:text-white transition">
                {{ Str::limit($post->thread->title, 30) }}
            </a>
            <span class="mx-2">›</span>
            <span class="text-white">{{ __('messages.forum_edit_post') }}</span>
        </nav>

        <h1 class="text-2xl font-bold text-white mb-6">{{ __('messages.forum_edit_post') }}</h1>

        <form method="POST" action="{{ route('forum.update-post', $post) }}"
              class="bg-gray-800/50 border border-gray-700 rounded-lg p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="body" class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.forum_thread_body') }}</label>
                <textarea name="body" id="body" rows="10"
                          class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                          required minlength="3">{{ old('body', $post->body) }}</textarea>
                @error('body')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between mt-5">
                <a href="{{ route('forum.thread', [$post->thread->category, $post->thread]) }}"
                   class="text-gray-400 hover:text-white transition">
                    {{ __('messages.forum_cancel') }}
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-medium">
                    <i class="fas fa-save mr-2"></i>{{ __('messages.forum_save') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
