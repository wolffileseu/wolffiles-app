@props([
    "user" => null,
    "size" => "sm",       // sm | md
    "label" => null,      // null = icon-only
])

@auth
    @if($user && auth()->id() !== $user->id)
        <a href="{{ route('dm.compose', ['to' => $user->id]) }}"
           class="inline-flex items-center gap-1 text-gray-400 hover:text-amber-400 transition-colors {{ $size === 'md' ? 'text-sm' : 'text-xs' }}"
           title="{{ __('messages.dm_send_message') }}">
            <svg class="{{ $size === 'md' ? 'w-4 h-4' : 'w-3.5 h-3.5' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            @if($label)
                <span>{{ $label }}</span>
            @endif
        </a>
    @endif
@endauth
