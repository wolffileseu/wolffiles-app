<x-layouts.app :title="'My Claims'">
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">My Claims</h1>
            <p class="text-gray-400 mt-1">Track the status of your profile, clan and server claims</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">&larr; Back to Tracker</a>
    </div>

    @if(session('success'))
    <div class="bg-green-900/30 border border-green-700 text-green-400 rounded-lg p-4 mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-900/30 border border-red-700 text-red-400 rounded-lg p-4 mb-6">{{ session('error') }}</div>
    @endif
    @if(session('info'))
    <div class="bg-blue-900/30 border border-blue-700 text-blue-400 rounded-lg p-4 mb-6">{{ session('info') }}</div>
    @endif

    @if($claims->isEmpty())
    <div class="text-center py-16 bg-gray-800 rounded-lg">
        <div class="text-4xl mb-4">📋</div>
        <p class="text-gray-400">You haven't submitted any claims yet.</p>
        <p class="text-gray-500 text-sm mt-2">Visit a player profile, clan page, or server page to claim it.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($claims as $claim)
        <div class="bg-gray-800 rounded-lg p-5 border border-gray-700/50 {{ $claim->status === 'pending' ? 'border-l-4 border-l-amber-500' : ($claim->status === 'approved' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-red-500') }}">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold
                        {{ $claim->claimable_type === 'player' ? 'bg-blue-900/50 text-blue-400' : ($claim->claimable_type === 'clan' ? 'bg-purple-900/50 text-purple-400' : 'bg-green-900/50 text-green-400') }}">
                        {{ $claim->claimable_type === 'player' ? '👤' : ($claim->claimable_type === 'clan' ? '🏰' : '🖥️') }}
                    </div>
                    <div>
                        <div class="text-white font-medium">
                            @if($claim->entity)
                                @if($claim->claimable_type === 'player')
                                    <a href="{{ route('tracker.player.show', $claim->entity) }}" class="hover:text-amber-400">
                                        {!! $claim->entity->name_html ?? e($claim->entity->name_clean ?? 'Unknown') !!}
                                    </a>
                                @elseif($claim->claimable_type === 'clan')
                                    <a href="{{ route('tracker.clan.show', $claim->entity) }}" class="hover:text-amber-400">
                                        [{{ $claim->entity->tag_clean }}] {{ $claim->entity->name ?? '' }}
                                    </a>
                                @else
                                    <a href="{{ route('tracker.server.show', $claim->entity) }}" class="hover:text-amber-400">
                                        {!! $claim->entity->hostname_html ?? e($claim->entity->hostname_clean ?? $claim->entity->full_address) !!}
                                    </a>
                                @endif
                            @else
                                <span class="text-gray-500">Entity deleted</span>
                            @endif
                        </div>
                        <div class="text-gray-500 text-xs">
                            {{ ucfirst($claim->claimable_type) }} claim &middot;
                            Submitted {{ $claim->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>

                <span class="px-3 py-1 rounded-full text-xs font-medium
                    {{ $claim->status === 'pending' ? 'bg-amber-900/30 text-amber-400' :
                       ($claim->status === 'approved' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400') }}">
                    {{ ucfirst($claim->status) }}
                </span>
            </div>

            <div class="mt-3 text-gray-400 text-sm bg-gray-700/30 rounded-lg p-3">
                <span class="text-gray-500 text-xs">Proof: {{ ucfirst(str_replace('_', ' ', $claim->proof_type)) }}</span><br>
                {{ Str::limit($claim->message, 200) }}
            </div>

            @if($claim->review_note)
            <div class="mt-3 text-sm bg-gray-700/30 rounded-lg p-3 border-l-2 {{ $claim->status === 'approved' ? 'border-l-green-500' : 'border-l-red-500' }}">
                <span class="text-gray-500 text-xs">Moderator response:</span><br>
                <span class="{{ $claim->status === 'approved' ? 'text-green-400' : 'text-red-400' }}">{{ $claim->review_note }}</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
</x-layouts.app>
