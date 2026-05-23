@props(['mod' => null, 'size' => 'sm'])

@php
    // Clean and normalize the mod name for icon filename lookup
    // - Strip ET color codes (^1, ^2, etc.)
    // - Strip version suffixes (e.g. "jaymod_2.1.7" -> "jaymod")
    // - Keep only lowercase a-z and 0-9
    $raw = (string) ($mod ?? '');
    $clean = \App\Services\Tracker\ColorCodeService::toClean($raw);
    $slug = strtolower(trim($clean));
    $slug = preg_replace('/[\s_\-.]*[vV]?[0-9]+([.\-_][0-9]+)*$/', '', $slug); // strip trailing version
    $slug = preg_replace('/[^a-z0-9]/', '', $slug);

    // Manual aliases for common mod-name variants that don't match their file directly
    $aliases = [
        'etlegacy'      => 'legacy',
        //'main'          => 'etmain',
        'silentmod'     => 'silent',
        'noquarter'     => 'nq',
        'truecombat'    => 'tc',
    ];
    $slug = $aliases[$slug] ?? $slug;

    // Auto-discover: image exists at public/images/mods/mod_<slug>.png ?
    $iconFile = $slug ? "mod_{$slug}" : null;
    $hasIcon  = $iconFile && file_exists(public_path("images/mods/{$iconFile}.png"));

    $sizeClass = match($size) {
        'xs' => 'w-4 h-4',
        'sm' => 'w-5 h-5',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
        default => 'w-5 h-5',
    };

    $displayName = $clean !== '' ? $clean : '-';
@endphp

@if($hasIcon)
    <img src="{{ asset('images/mods/' . $iconFile . '.png') }}"
         alt="{{ $displayName }}"
         title="{{ $displayName }}"
         class="{{ $sizeClass }} inline-block align-middle"
         loading="lazy">
@elseif($clean !== '')
    <span class="text-gray-400 text-xs">{{ $displayName }}</span>
@else
    <span class="text-gray-600 text-xs">-</span>
@endif
