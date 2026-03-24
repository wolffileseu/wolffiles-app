@php
    $typeKey = [
        'news'        => 'post_type_news',
        'event'       => 'post_type_event',
        'match'       => 'post_type_match',
        'recruitment' => 'post_type_recruitment',
    ];
    $colors = [
        'news'        => 'bg-blue-900/30 text-blue-400',
        'event'       => 'bg-green-900/30 text-green-400',
        'match'       => 'bg-yellow-900/30 text-yellow-400',
        'recruitment' => 'bg-red-900/30 text-red-400',
    ];
    $icons = [
        'news'        => '📰',
        'event'       => '📅',
        'match'       => '⚔️',
        'recruitment' => '🎮',
    ];
    $color = $colors[$post->type] ?? 'bg-gray-700 text-gray-400';
    $icon  = $icons[$post->type] ?? '📄';
    $key   = $typeKey[$post->type] ?? null;
    $label = $key ? __('messages.' . $key) : ($post->type);
@endphp
<span class="{{ $color }} px-2 py-0.5 rounded text-xs font-medium">{{ $icon }} {{ $label }}</span>
