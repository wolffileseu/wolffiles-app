{{-- Comments Component --}}
{{-- Usage: @include('components.comments', ['commentable' => $file, 'type' => 'file']) --}}

@php
    $comments = $commentable->comments()
        ->where('is_approved', true)
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
    <h3 class="text-xl font-bold text-white mb-6">{{ __('messages.comments') }} ({{ $comments->count() }})</h3>

    {{-- Comment Form --}}
    @auth
        <form method="POST" action="{{ route('comments.store') }}" class="mb-6">
            @csrf
            <input type="hidden" name="commentable_type" value="{{ get_class($commentable) }}">
            <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">

            <textarea name="body" rows="3" required maxlength="2000" placeholder="Write a comment..."
                      class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500 mb-3">{{ old('body') }}</textarea>
            @error('body') <p class="text-red-400 text-sm mb-2">{{ $message }}</p> @enderror

            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                Post Comment
            </button>
        </form>
    @else
        <p class="text-gray-400 mb-6">
            <a href="{{ route('login') }}" class="text-amber-400 hover:underline">{{ __('messages.login') }}</a> to leave a comment.
        </p>
    @endauth

    {{-- Comments List --}}
    @forelse($comments as $comment)
        <div class="border-t border-gray-700 py-4 {{ !$loop->first ? '' : 'border-t-0' }}">
            <div class="flex items-start space-x-3">
                <img src="{{ $comment->user?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&background=random' }}" class="w-8 h-8 rounded-full flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2 mb-1">
                        <a href="{{ route('profile.show', $comment->user ?? 1) }}" class="text-amber-400 text-sm font-medium hover:underline">
                            {{ $comment->user?->name ?? 'Unknown' }}
                        </a>
                        <span class="text-gray-500 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-gray-300 text-sm">{{ $comment->body }}</p>
                </div>
                @if(auth()->id() === $comment->user_id || auth()->user()?->isModerator())
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="flex-shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-500 hover:text-red-400 text-xs" onclick="return confirm('Delete this comment?')">✕</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-sm">No comments yet. Be the first!</p>
    @endforelse
</div>
