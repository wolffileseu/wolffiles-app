<x-layouts.app title="Upload Demo">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-6">🎬 Upload Demo</h1>

        @if($errors->any())
            <div class="bg-red-900/50 border border-red-700 text-red-300 rounded-lg p-4 mb-6">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('demos.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Basic Info --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-white mb-2">Basic Info</h2>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-1">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="e.g. fnatic vs idle - Supply SW"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-1">Description</label>
                    <textarea name="description" rows="4" placeholder="Add details about the demo..."
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Category *</label>
                        <select name="category_id" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                            <option value="">Select category...</option>
                            @foreach($categories as $cat)
                                @if($cat->parent)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->parent->name }} → {{ $cat->name }}
                                    </option>
                                @else
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Game *</label>
                        <select name="game" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                            <option value="ET" {{ old('game', 'ET') == 'ET' ? 'selected' : '' }}>Enemy Territory</option>
                            <option value="RtCW" {{ old('game') == 'RtCW' ? 'selected' : '' }}>Return to Castle Wolfenstein</option>
                            <option value="Q3" {{ old('game') == 'Q3' ? 'selected' : '' }}>Quake 3 Arena</option>
                            <option value="ETQW" {{ old('game') == 'ETQW' ? 'selected' : '' }}>ET: Quake Wars</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Game Details --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-white mb-2">Game Details</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Map</label>
                        <input type="text" name="map_name" value="{{ old('map_name') }}" placeholder="e.g. supply"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Mod</label>
                        <select name="mod_name" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                            <option value="">Select mod...</option>
                            @foreach(['etpro' => 'ETPro', 'jaymod' => 'Jaymod', 'nitmod' => 'N!tmod', 'legacy' => 'ET: Legacy', 'silent' => 'Silent Mod', 'noquarter' => 'NoQuarter', 'shrub' => 'Shrub', 'etpub' => 'ETPub', 'etjump' => 'ETJump'] as $k => $v)
                                <option value="{{ $k }}" {{ old('mod_name') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Gametype</label>
                        <select name="gametype" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                            <option value="">Select...</option>
                            @foreach(['stopwatch' => 'Stopwatch', 'objective' => 'Objective', 'lms' => 'Last Man Standing', 'ctf' => 'CTF'] as $k => $v)
                                <option value="{{ $k }}" {{ old('gametype') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Match Format</label>
                        <select name="match_format" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                            <option value="">Select...</option>
                            @foreach(['6on6' => '6on6', '5on5' => '5on5', '3on3' => '3on3', '2on2' => '2on2', '1on1' => '1on1', 'public' => 'Public'] as $k => $v)
                                <option value="{{ $k }}" {{ old('match_format') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Match Info --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-white mb-2">Match Info <span class="text-gray-500 text-sm font-normal">(optional)</span></h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Team Axis / Team 1</label>
                        <input type="text" name="team_axis" value="{{ old('team_axis') }}" placeholder="e.g. fnatic"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Team Allies / Team 2</label>
                        <input type="text" name="team_allies" value="{{ old('team_allies') }}" placeholder="e.g. idle"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Match Date</label>
                        <input type="date" name="match_date" value="{{ old('match_date') }}"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Recorded by</label>
                        <input type="text" name="recorder_name" value="{{ old('recorder_name') }}" placeholder="Player name"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Source</label>
                        <input type="text" name="match_source" value="{{ old('match_source') }}" placeholder="e.g. GamesTV, ESL"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-1">Source URL</label>
                        <input type="url" name="match_source_url" value="{{ old('match_source_url') }}" placeholder="https://..."
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-1">Server Name</label>
                    <input type="text" name="server_name" value="{{ old('server_name') }}" placeholder="e.g. FA #1 Jaymod"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>
            </div>

            {{-- File Upload --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-white mb-2">Demo File *</h2>
                <input type="file" name="file" required accept=".dm_84,.dm_83,.dm_82,.dm_60,.tv_84,.zip,.rar,.7z,.gz"
                       class="w-full text-gray-300 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-amber-600 file:text-white hover:file:bg-amber-700">
                <p class="text-gray-500 text-xs">Accepted: .dm_84, .dm_83, .dm_82, .dm_60, .tv_84, .zip, .rar, .7z (max {{ config('app.max_upload_size', 500) }}MB)</p>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-1">Screenshots (optional)</label>
                    <input type="file" name="screenshots[]" multiple accept="image/*"
                           class="w-full text-gray-300 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gray-600 file:text-white">
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end space-x-4">
                <a href="{{ route('demos.index') }}" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-6 py-3 rounded-lg font-medium">Cancel</a>
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-bold text-lg">
                    Upload Demo
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
