<x-layouts.app :title="$title">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-4">
            <a href="{{ route('dm.inbox') }}" class="text-sm text-amber-400 hover:text-amber-300">
                ← {{ __('Back to inbox') }}
            </a>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h1 class="text-2xl font-bold text-white mb-4">✏️ {{ __('New message') }}</h1>
            <p class="text-gray-400 italic">
                {{ __('Compose form coming in Phase 4d.') }}
            </p>
            @if($recipientId)
                <p class="text-sm text-gray-500 mt-2">{{ __('Recipient ID') }}: {{ $recipientId }}</p>
            @endif
        </div>
    </div>
</x-layouts.app>
