<x-layouts.app :title="'Propose Clan'">
<div class="max-w-3xl mx-auto px-4 py-8">

    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Propose a Clan</h1>
            <p class="text-gray-400 mt-1 text-sm">Your clan isn't auto-detected yet? Propose it and an admin will review.</p>
        </div>
        <a href="{{ route('tracker.clans') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to clans</a>
    </div>

    @if(session('success'))<div class="mb-4 px-4 py-3 bg-green-900/30 border border-green-500/30 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('clans.propose.store') }}" class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Clan Tag (Display)</label>
                <input name="tag" required value="{{ old('tag') }}" placeholder="[RoG] / =RoG= / .RoG"
                       class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">
                <p class="mt-1 text-xs text-gray-500">Wie der Tag im Spiel aussieht.</p>
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Tag Clean (Matching)</label>
                <input name="tag_clean" required value="{{ old('tag_clean') }}" placeholder="RoG"
                       class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">
                <p class="mt-1 text-xs text-gray-500">Nur Buchstaben/Zahlen/Symbole, fürs Matching.</p>
            </div>
        </div>

        <div>
            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Clan Name (optional)</label>
            <input name="name" value="{{ old('name') }}" placeholder="Rebels of Gaming"
                   class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
        </div>

        <div>
            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Description (optional)</label>
            <textarea name="description" rows="3" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Website</label>
                <input name="website" value="{{ old('website') }}" placeholder="https://"
                       class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Discord</label>
                <input name="discord" value="{{ old('discord') }}" placeholder="discord.gg/..."
                       class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
            </div>
        </div>

        <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">Submit Proposal</button>
    </form>

    @if($mine->count() > 0)
    <div class="mt-8">
        <h2 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Your Recent Proposals</h2>
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2 text-left">Tag</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Submitted</th>
                        <th class="px-4 py-2 text-left">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($mine as $p)
                    <tr class="text-gray-200">
                        <td class="px-4 py-3 font-mono">{{ $p->tag }}</td>
                        <td class="px-4 py-3">
                            @php $sc = ['pending'=>'amber','approved'=>'green','merged'=>'blue','rejected'=>'red'][$p->status] ?? 'gray'; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs uppercase tracking-wide border text-{{ $sc }}-400 border-{{ $sc }}-500/40 bg-{{ $sc }}-900/10">{{ $p->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $p->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-xs text-gray-400">{{ $p->review_note ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
</x-layouts.app>
