<x-layouts.app :title="'Manage [' . $clan->tag . '] ' . $clan->name">
@php
    $tc = $clan->trackerClan;
    $isOwner = $manager->role === 'owner';
    $isAdmin = in_array($manager->role, ['owner','admin']);
    $roleLabels = ['Leader','Co-Leader','Recruiter','Member','Trial','Inactive'];
@endphp

<div class="max-w-7xl mx-auto px-4 py-8" x-data="{ tab: 'content' }">

    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
            <a href="{{ $tc ? route('tracker.clan.show', $tc) : '#' }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; View public page</a>
            <h1 class="text-2xl font-bold text-white mt-1"><span class="text-amber-500">[{{ $clan->tag }}]</span> {{ $clan->name }} <span class="text-gray-500 text-base font-normal">&middot; Manage</span></h1>
        </div>
        <span class="px-3 py-1.5 bg-gray-700 text-amber-400 rounded-lg text-xs uppercase tracking-wide border border-amber-500/30">Your role: {{ $manager->role }}</span>
    </div>

    @if(session('success'))<div class="mb-4 px-4 py-3 bg-green-900/30 border border-green-500/30 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-gray-700 mb-6 flex-wrap">
        <button @click="tab='content'" :class="tab==='content' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Content</button>
        <button @click="tab='members'" :class="tab==='members' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Members<span class="text-gray-600 ml-1">{{ $members->count() }}</span></button>
        <button @click="tab='news'" :class="tab==='news' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">News<span class="text-gray-600 ml-1">{{ $news->count() }}</span></button>
        @if($isAdmin)
        <button @click="tab='managers'" :class="tab==='managers' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Managers<span class="text-gray-600 ml-1">{{ $clan->managers->count() }}</span></button>
        <button @click="tab='apps'" :class="tab==='apps' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Applications<span class="text-gray-600 ml-1">{{ $applications->where('status','pending')->count() }}</span></button>
        @endif
    </div>

    {{-- ========================= CONTENT ========================= --}}
    <div x-show="tab==='content'">
        <form method="POST" action="{{ route('clan.manage.content', $clan->slug) }}" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-5">
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Clan Name</label>
                            <input name="name" value="{{ old('name', $clan->name) }}" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">About (Markdown + BBCode)</label>
                            <textarea name="description" rows="6" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('description', $clan->description) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Server Rules</label>
                            <textarea name="rules" rows="5" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('rules', $clan->rules) }}</textarea>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Recruitment</h3>
                        <label class="flex items-center gap-2 text-sm text-gray-300">
                            <input type="checkbox" name="is_recruiting" value="1" {{ old('is_recruiting', $clan->is_recruiting) ? 'checked' : '' }} class="rounded bg-gray-900 border-gray-600 text-amber-500">
                            We are recruiting
                        </label>
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Requirements / Summary</label>
                            <textarea name="recruitment_summary" rows="4" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">{{ old('recruitment_summary', $clan->recruitment_summary) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Info & Links</h3>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Location</label><input name="location" value="{{ old('location', $clan->location) }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Founded</label><input name="founded" value="{{ old('founded', $clan->founded) }}" placeholder="e.g. 2008" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Website</label><input name="website" value="{{ old('website', $clan->website) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Discord</label><input name="contact_discord" value="{{ old('contact_discord', $clan->contact_discord) }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">TS / Vent</label><input name="ts_address" value="{{ old('ts_address', $clan->ts_address) }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                    </div>
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Images (URL)</h3>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Logo URL</label><input name="logo" value="{{ old('logo', $clan->logo) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Banner URL</label><input name="banner" value="{{ old('banner', $clan->banner) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                    </div>
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
                        <label class="flex items-center gap-2 text-sm text-gray-300">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $clan->is_published) ? 'checked' : '' }} class="rounded bg-gray-900 border-gray-600 text-amber-500">
                            Page published (visible to public)
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">Save Changes</button>
        </form>
    </div>

    {{-- ========================= MEMBERS ========================= --}}
    <div x-show="tab==='members'" x-cloak class="space-y-5">
        @if(!$tc)
        <div class="bg-amber-900/10 border border-amber-500/20 rounded-lg p-4 text-sm text-gray-400">No tracker clan linked — members are auto-detected once players with the tag are seen.</div>
        @else
        @if($isAdmin)
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Squads</h3>
            <div class="flex flex-wrap gap-2 mb-3">
                @forelse($squads as $squad)
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-900 border border-gray-600 rounded-lg text-sm text-gray-300">
                    {{ $squad->name }}
                    <form method="POST" action="{{ route('clan.manage.squad.delete', [$clan->slug, $squad->id]) }}" onsubmit="return confirm('Delete squad?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300">&times;</button></form>
                </span>
                @empty
                <span class="text-gray-500 text-sm">No squads yet.</span>
                @endforelse
            </div>
            <form method="POST" action="{{ route('clan.manage.squad.store', $clan->slug) }}" class="flex gap-2">
                @csrf
                <input name="name" placeholder="New squad name..." required class="flex-1 bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <button class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">+ Add</button>
            </form>
        </div>
        @endif

        <div class="bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h3 class="text-white font-semibold">Member Roster — assign roles & squads</h3></div>
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/30">
                    <tr><th class="px-4 py-2">Player</th><th class="px-4 py-2">Role Label</th><th class="px-4 py-2">Squad</th><th class="px-4 py-2 w-20">Order</th><th class="px-4 py-2"></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($members as $m)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-4 py-2 font-mono text-amber-400">{!! $m->player->name_html ?? e($m->player->name_clean ?? 'Unknown') !!}</td>
                        <form method="POST" action="{{ route('clan.manage.member', [$clan->slug, $m->id]) }}">
                            @csrf @method('PUT')
                            <td class="px-4 py-2">
                                <select name="role_label" class="bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs">
                                    <option value="">—</option>
                                    @foreach($roleLabels as $rl)<option value="{{ $rl }}" {{ $m->role_label === $rl ? 'selected' : '' }}>{{ $rl }}</option>@endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <select name="squad_id" class="bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs">
                                    <option value="">—</option>
                                    @foreach($squads as $squad)<option value="{{ $squad->id }}" {{ $m->squad_id == $squad->id ? 'selected' : '' }}>{{ $squad->name }}</option>@endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2"><input name="sort_order" type="number" value="{{ $m->sort_order }}" class="w-16 bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs"></td>
                            <td class="px-4 py-2"><button class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-amber-400 rounded text-xs">Save</button></td>
                        </form>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ========================= NEWS ========================= --}}
    <div x-show="tab==='news'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-3">
            @forelse($news as $post)
            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-4 flex items-start justify-between gap-3">
                <div>
                    <h4 class="text-white font-semibold">{{ $post->title }} @unless($post->is_published)<span class="text-xs text-amber-400">(draft)</span>@endunless</h4>
                    <div class="text-xs text-gray-500 font-mono">{{ $post->created_at->diffForHumans() }}</div>
                </div>
                <form method="POST" action="{{ route('clan.manage.news.delete', [$clan->slug, $post->id]) }}" onsubmit="return confirm('Delete this news post?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300 text-sm">Delete</button></form>
            </div>
            @empty
            <p class="text-gray-500 text-sm">No news yet.</p>
            @endforelse
        </div>
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 self-start">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Post News</h3>
            <form method="POST" action="{{ route('clan.manage.news.store', $clan->slug) }}" class="space-y-3">
                @csrf
                <input name="title" placeholder="Title" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <input name="excerpt" placeholder="Short excerpt (optional)" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <textarea name="content" rows="5" placeholder="Content (Markdown + BBCode)" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500"></textarea>
                <button class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">Publish</button>
            </form>
        </div>
    </div>

    {{-- ========================= MANAGERS ========================= --}}
    @if($isAdmin)
    <div x-show="tab==='managers'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h3 class="text-white font-semibold">Clan Managers</h3></div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($clan->managers as $mgr)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-gray-200">{{ $mgr->user->name ?? 'Unknown' }} @if($mgr->user_id === auth()->id())<span class="text-gray-500 text-xs">(you)</span>@endif</td>
                        <td class="px-4 py-3">
                            @if($mgr->role === 'owner')
                                <span class="px-2 py-0.5 rounded-full text-xs uppercase tracking-wide border text-amber-400 border-amber-500/40 bg-amber-900/10">owner</span>
                            @elseif($isOwner)
                                <form method="POST" action="{{ route('clan.manage.manager.update', [$clan->slug, $mgr->id]) }}" class="inline">
                                    @csrf @method('PUT')
                                    <select name="role" onchange="this.form.submit()" class="bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs">
                                        <option value="admin" {{ $mgr->role==='admin'?'selected':'' }}>admin</option>
                                        <option value="editor" {{ $mgr->role==='editor'?'selected':'' }}>editor</option>
                                    </select>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs uppercase">{{ $mgr->role }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($mgr->role !== 'owner')
                            <form method="POST" action="{{ route('clan.manage.manager.delete', [$clan->slug, $mgr->id]) }}" onsubmit="return confirm('Remove this manager?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300 text-xs">Remove</button></form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 self-start">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Add Manager</h3>
            <form method="POST" action="{{ route('clan.manage.manager.store', $clan->slug) }}" class="space-y-3">
                @csrf
                <input name="identifier" placeholder="Username or email" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <select name="role" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm">
                    <option value="editor">editor (content + news)</option>
                    <option value="admin">admin (everything but delete)</option>
                </select>
                <button class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">+ Invite</button>
            </form>
            <p class="text-xs text-gray-500 mt-3"><b class="text-gray-400">owner</b>: all + delete/transfer · <b class="text-gray-400">admin</b>: manage members/managers/apps · <b class="text-gray-400">editor</b>: content & news only</p>
        </div>
    </div>

    {{-- ========================= APPLICATIONS ========================= --}}
    <div x-show="tab==='apps'" x-cloak class="space-y-3">
        @forelse($applications as $app)
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-4 flex items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h4 class="text-white font-semibold">{{ $app->player_name }}</h4>
                    @php $sc = ['pending'=>'amber','accepted'=>'green','rejected'=>'red','withdrawn'=>'gray'][$app->status] ?? 'gray'; @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs uppercase tracking-wide border text-{{ $sc }}-400 border-{{ $sc }}-500/40 bg-{{ $sc }}-900/10">{{ $app->status }}</span>
                </div>
                @if($app->contact)<div class="text-xs text-gray-500 font-mono mt-0.5">{{ $app->contact }}</div>@endif
                <p class="text-sm text-gray-300 mt-2">{{ $app->message }}</p>
                <div class="text-xs text-gray-500 font-mono mt-1">{{ $app->created_at->diffForHumans() }}@if($app->applicant) · user: {{ $app->applicant->name }}@endif</div>
            </div>
            @if($app->status === 'pending')
            <div class="flex flex-col gap-2 shrink-0">
                <form method="POST" action="{{ route('clan.manage.app.review', [$clan->slug, $app->id]) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="accepted"><button class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded text-xs w-full">Accept</button></form>
                <form method="POST" action="{{ route('clan.manage.app.review', [$clan->slug, $app->id]) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="rejected"><button class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded text-xs w-full">Reject</button></form>
            </div>
            @endif
        </div>
        @empty
        <p class="text-gray-500 text-sm">No applications yet.</p>
        @endforelse
    </div>
    @endif

</div>
</x-layouts.app>
