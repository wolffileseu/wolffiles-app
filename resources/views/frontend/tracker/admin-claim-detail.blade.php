<x-layouts.app :title="'Review Claim #' . $claim->id">
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.admin.claims') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Claims</a>

    {{-- Claim Header --}}
    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-white">
                Claim #{{ $claim->id }}
                <span class="px-3 py-1 rounded-full text-sm font-medium ml-2
                    {{ $claim->status === 'pending' ? 'bg-amber-900/30 text-amber-400' :
                       ($claim->status === 'approved' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400') }}">
                    {{ ucfirst($claim->status) }}
                </span>
            </h1>
            <span class="px-2 py-0.5 rounded text-xs font-medium
                {{ $claim->claimable_type === 'player' ? 'bg-blue-900/30 text-blue-400' : 'bg-purple-900/30 text-purple-400' }}">
                {{ ucfirst($claim->claimable_type) }} Claim
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Entity Info --}}
            <div class="bg-gray-700/30 rounded-lg p-4">
                <h3 class="text-gray-400 text-xs uppercase tracking-wider mb-2">Claimed {{ ucfirst($claim->claimable_type) }}</h3>
                @if($claim->entity)
                    @if($claim->claimable_type === 'player')
                    <div class="flex items-center gap-3">
                        @if($claim->entity->country_code)
                        <img src="https://flagcdn.com/{{ strtolower($claim->entity->country_code) }}.svg" class="w-5 h-3 rounded-sm" alt="">
                        @endif
                        <a href="{{ route('tracker.player.show', $claim->entity) }}" class="text-amber-400 hover:text-amber-300 font-semibold">
                            {!! $claim->entity->name_html ?? e($claim->entity->name_clean ?? 'Unknown') !!}
                        </a>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-3 text-center text-xs">
                        <div><div class="text-white font-bold">{{ number_format($claim->entity->elo_rating) }}</div><div class="text-gray-500">ELO</div></div>
                        <div><div class="text-white font-bold">{{ round($claim->entity->total_play_time_minutes / 60, 1) }}h</div><div class="text-gray-500">Playtime</div></div>
                        <div><div class="text-white font-bold">{{ $claim->entity->last_seen_at?->diffForHumans() ?? '-' }}</div><div class="text-gray-500">Last Seen</div></div>
                    </div>
                    @if($claim->entity->claimed_by_user_id)
                    <div class="mt-3 text-green-400 text-xs">✓ Already claimed by user #{{ $claim->entity->claimed_by_user_id }}</div>
                    @endif
                    @else
                    <a href="{{ route('tracker.clan.show', $claim->entity) }}" class="text-amber-400 hover:text-amber-300 font-semibold">
                        [{{ $claim->entity->tag_clean }}] {{ $claim->entity->name ?? '' }}
                    </a>
                    <div class="grid grid-cols-3 gap-2 mt-3 text-center text-xs">
                        <div><div class="text-white font-bold">{{ $claim->entity->member_count }}</div><div class="text-gray-500">Members</div></div>
                        <div><div class="text-white font-bold">{{ number_format($claim->entity->avg_elo) }}</div><div class="text-gray-500">Avg ELO</div></div>
                        <div><div class="text-white font-bold">{{ $claim->entity->last_seen_at?->diffForHumans() ?? '-' }}</div><div class="text-gray-500">Last Seen</div></div>
                    </div>
                    @if($claim->entity->claimed_by_user_id)
                    <div class="mt-3 text-green-400 text-xs">✓ Already claimed by user #{{ $claim->entity->claimed_by_user_id }}</div>
                    @endif
                    @endif
                @else
                    <span class="text-gray-500">Entity has been deleted</span>
                @endif
            </div>

            {{-- Claimant Info --}}
            <div class="bg-gray-700/30 rounded-lg p-4">
                <h3 class="text-gray-400 text-xs uppercase tracking-wider mb-2">Claimed By</h3>
                <div class="text-white font-semibold">{{ $claim->user->name ?? 'Unknown' }}</div>
                <div class="text-gray-400 text-xs">{{ $claim->user->email ?? '' }}</div>
                <div class="text-gray-500 text-xs mt-2">
                    Member since {{ $claim->user->created_at?->format('M Y') ?? '-' }}
                </div>
                <div class="text-gray-500 text-xs mt-1">Submitted {{ $claim->created_at->format('d M Y H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- Proof / Message --}}
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-white font-semibold mb-3">Proof & Message</h2>
        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-0.5 bg-gray-700 rounded text-xs text-gray-300">{{ ucfirst(str_replace('_', ' ', $claim->proof_type)) }}</span>
        </div>
        <div class="bg-gray-700/30 rounded-lg p-4 text-gray-300 whitespace-pre-wrap">{{ $claim->message }}</div>
    </div>

    {{-- Clan Extra Details (if clan claim) --}}
    @if($claim->claimable_type === 'clan' && ($claim->clan_email || $claim->clan_website || $claim->clan_discord || $claim->clan_description))
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-white font-semibold mb-3">Submitted Clan Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($claim->clan_email)
            <div><span class="text-gray-500 text-xs">Email:</span><div class="text-white text-sm">{{ $claim->clan_email }}</div></div>
            @endif
            @if($claim->clan_website)
            <div><span class="text-gray-500 text-xs">Website:</span><div class="text-white text-sm"><a href="{{ $claim->clan_website }}" target="_blank" class="text-amber-400 hover:text-amber-300">{{ $claim->clan_website }}</a></div></div>
            @endif
            @if($claim->clan_discord)
            <div><span class="text-gray-500 text-xs">Discord:</span><div class="text-white text-sm">{{ $claim->clan_discord }}</div></div>
            @endif
        </div>
        @if($claim->clan_description)
        <div class="mt-4">
            <span class="text-gray-500 text-xs">Description:</span>
            <div class="text-gray-300 text-sm mt-1 bg-gray-700/30 rounded-lg p-3">{{ $claim->clan_description }}</div>
        </div>
        @endif
    </div>
    @endif

    {{-- Other Claims for same entity --}}
    @if($otherClaims->count())
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-white font-semibold mb-3">Other Claims for this {{ ucfirst($claim->claimable_type) }}</h2>
        <div class="space-y-2">
            @foreach($otherClaims as $other)
            <div class="flex items-center justify-between bg-gray-700/30 rounded-lg p-3">
                <div>
                    <span class="text-white text-sm">{{ $other->user->name ?? 'Unknown' }}</span>
                    <span class="text-gray-500 text-xs ml-2">{{ $other->created_at->diffForHumans() }}</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs
                    {{ $other->status === 'pending' ? 'bg-amber-900/30 text-amber-400' :
                       ($other->status === 'approved' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400') }}">
                    {{ ucfirst($other->status) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Review History --}}
    @if($claim->reviewed_at)
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-white font-semibold mb-3">Review Decision</h2>
        <div class="flex items-center gap-2 mb-2">
            <span class="px-3 py-1 rounded-full text-sm font-medium
                {{ $claim->status === 'approved' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400' }}">
                {{ ucfirst($claim->status) }}
            </span>
            <span class="text-gray-500 text-xs">by {{ $claim->reviewer->name ?? 'System' }} &middot; {{ $claim->reviewed_at->format('d M Y H:i') }}</span>
        </div>
        @if($claim->review_note)
        <div class="bg-gray-700/30 rounded-lg p-3 text-gray-300 text-sm">{{ $claim->review_note }}</div>
        @endif
    </div>
    @endif

    {{-- Action Buttons (only for pending) --}}
    @if($claim->isPending())
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Approve --}}
        <form method="POST" action="{{ route('tracker.admin.claims.approve', $claim) }}" class="bg-green-900/10 border border-green-800/30 rounded-lg p-5">
            @csrf
            <h3 class="text-green-400 font-semibold mb-3">✓ Approve Claim</h3>
            <div class="mb-3">
                <textarea name="review_note" rows="2" placeholder="Optional note to the user..."
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-green-500"></textarea>
            </div>
            <button type="submit" class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg font-medium hover:bg-green-500 transition"
                    onclick="return confirm('Approve this claim? The profile/clan will be linked to this user.')">
                Approve
            </button>
        </form>

        {{-- Reject --}}
        <form method="POST" action="{{ route('tracker.admin.claims.reject', $claim) }}" class="bg-red-900/10 border border-red-800/30 rounded-lg p-5">
            @csrf
            <h3 class="text-red-400 font-semibold mb-3">✗ Reject Claim</h3>
            <div class="mb-3">
                <textarea name="review_note" rows="2" required placeholder="Reason for rejection (required)..."
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-red-500"></textarea>
                @error('review_note') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-500 transition"
                    onclick="return confirm('Reject this claim?')">
                Reject
            </button>
        </form>
    </div>
    @endif
</div>
</x-layouts.app>
