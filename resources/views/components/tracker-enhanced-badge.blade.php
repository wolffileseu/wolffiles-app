@props([
    'size' => 'sm',           // sm | md | lg
    'variant' => 'default',   // default | compact | inline
    'tooltip' => true,
])

@php
$sizeClasses = match($size) {
    'lg' => 'text-sm px-2.5 py-1',
    'md' => 'text-xs px-2 py-0.5',
    default => 'text-[10px] px-1.5 py-0.5',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full bg-emerald-500/15 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300 font-semibold uppercase tracking-wider border border-emerald-500/30 {$sizeClasses}"]) }}
      @if($tooltip) title="{{ __('This server/player reports detailed stats via Enhanced Tracker (sv_tracker)') }}" @endif>
    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
    </svg>
    @if($variant !== 'compact')
        <span>Enhanced</span>
    @endif
</span>
