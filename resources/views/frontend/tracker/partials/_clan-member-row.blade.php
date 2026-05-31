{{-- frontend/tracker/partials/_clan-member-row.blade.php
     Expects: $m (TrackerClanMember with ->player loaded) --}}
@php
    $roleColors = [
        'Leader'    => 'text-amber-400 border-amber-500/40 bg-amber-900/10',
        'Co-Leader' => 'text-amber-400 border-amber-500/40 bg-amber-900/10',
        'Recruiter' => 'text-blue-400 border-blue-500/40 bg-blue-900/10',
        'Trial'     => 'text-gray-400 border-gray-600 bg-gray-900/20',
        'Inactive'  => 'text-gray-500 border-gray-700 bg-gray-900/20',
    ];
    $rl = $m->role_label;
    $badge = $rl ? ($roleColors[$rl] ?? 'text-gray-400 border-gray-600 bg-gray-900/20') : null;
@endphp
<tr class="hover:bg-gray-700/30 transition">
    <td class="px-4 py-2">
        <a href="{{ route('tracker.player.show', $m->player) }}" class="text-amber-400 hover:text-amber-300 font-mono text-sm">
            {!! $m->player->name_html ?? e($m->player->name_clean ?? 'Unknown') !!}
        </a>
    </td>
    <td class="px-4 py-2">
        @if($rl)
            <span class="inline-block px-2 py-0.5 rounded-full text-xs uppercase tracking-wide border {{ $badge }}">{{ $rl }}</span>
        @else
            <span class="text-gray-600 text-xs">&mdash;</span>
        @endif
    </td>
    <td class="px-4 py-2 text-center text-white font-medium">{{ $m->player->elo_rating !== null ? number_format($m->player->elo_rating) : '-' }}</td>
    <td class="px-4 py-2 text-gray-400 text-xs">{{ $m->player->last_seen_at?->diffForHumans() ?? '-' }}</td>
</tr>
