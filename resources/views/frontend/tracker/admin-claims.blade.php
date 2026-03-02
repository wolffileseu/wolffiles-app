<x-layouts.app :title="'Manage Claims'">
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Manage Claims</h1>
            <p class="text-gray-400 mt-1">Review player and clan ownership claims</p>
        </div>
        @if($pendingCount > 0)
        <span class="px-4 py-2 bg-amber-900/30 text-amber-400 rounded-lg text-sm font-medium">
            {{ $pendingCount }} pending
        </span>
        @endif
    </div>

    {{-- Status Filter --}}
    <div class="flex gap-2 mb-6">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
        <a href="{{ route('tracker.admin.claims', ['status' => $key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $status === $key ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    @if(session('success'))
    <div class="bg-green-900/30 border border-green-700 text-green-400 rounded-lg p-4 mb-6">{{ session('success') }}</div>
    @endif

    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="text-gray-400 text-left bg-gray-900/50">
                <tr>
                    <th class="px-4 py-3 w-12">#</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Entity</th>
                    <th class="px-4 py-3">Claimed by</th>
                    <th class="px-4 py-3">Proof</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700/50">
                @forelse($claims as $claim)
                <tr class="hover:bg-gray-750 transition {{ $claim->isPending() ? 'bg-amber-900/5' : '' }}">
                    <td class="px-4 py-3 text-gray-500">{{ $claim->id }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            {{ $claim->claimable_type === 'player' ? 'bg-blue-900/30 text-blue-400' : 'bg-purple-900/30 text-purple-400' }}">
                            {{ ucfirst($claim->claimable_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($claim->entity)
                            @if($claim->claimable_type === 'player')
                                <a href="{{ route('tracker.player.show', $claim->entity) }}" class="text-amber-400 hover:text-amber-300">
                                    {!! $claim->entity->name_html ?? e($claim->entity->name_clean ?? 'Unknown') !!}
                                </a>
                            @else
                                <a href="{{ route('tracker.clan.show', $claim->entity) }}" class="text-amber-400 hover:text-amber-300">
                                    [{{ $claim->entity->tag_clean }}] {{ $claim->entity->name ?? '' }}
                                </a>
                            @endif
                        @else
                            <span class="text-gray-500">Deleted</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-white">{{ $claim->user->name ?? 'Unknown' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ ucfirst(str_replace('_', ' ', $claim->proof_type)) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $claim->status === 'pending' ? 'bg-amber-900/30 text-amber-400' :
                               ($claim->status === 'approved' ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400') }}">
                            {{ ucfirst($claim->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $claim->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('tracker.admin.claims.show', $claim) }}" class="text-amber-400 hover:text-amber-300 text-xs font-medium">
                            {{ $claim->isPending() ? 'Review' : 'View' }} &rarr;
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">No claims found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($claims->hasPages()) <div class="mt-4">{{ $claims->links() }}</div> @endif
</div>
</x-layouts.app>
