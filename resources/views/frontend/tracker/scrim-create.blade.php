<x-layouts.app :title="'Create Match'">
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.scrims') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Matches</a>
    <h1 class="text-3xl font-bold text-amber-500 mt-4 mb-6">Create Match</h1>

    <form method="POST" action="{{ route('tracker.scrims.store') }}" class="space-y-6">
        @csrf
        <div>
            <label for="title" class="block text-sm text-gray-300 mb-1">Title *</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g. Looking for 3v3 Stopwatch match"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
            @error('title')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="block text-sm text-gray-300 mb-1">Description</label>
            <textarea name="description" id="description" rows="3" placeholder="Describe what you're looking for..."
                      class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="game_type" class="block text-sm text-gray-300 mb-1">Game Type *</label>
                <select name="game_type" id="game_type" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                    @foreach(['1v1','2v2','3v3','5v5','6v6','mix'] as $gt)<option value="{{ $gt }}" {{ old('game_type','3v3')===$gt?'selected':'' }}>{{ $gt === 'mix' ? 'Mix / Gather' : $gt }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="skill_level" class="block text-sm text-gray-300 mb-1">Skill Level</label>
                <select name="skill_level" id="skill_level" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                    <option value="">Any</option>
                    @foreach(['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $k=>$v)<option value="{{ $k }}" {{ old('skill_level')===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="map_preference" class="block text-sm text-gray-300 mb-1">Map Preference</label>
                <input type="text" name="map_preference" id="map_preference" value="{{ old('map_preference') }}" placeholder="e.g. supply, goldrush"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
            </div>
            <div>
                <label for="mod_preference" class="block text-sm text-gray-300 mb-1">Mod Preference</label>
                <input type="text" name="mod_preference" id="mod_preference" value="{{ old('mod_preference') }}" placeholder="e.g. ETPro, Legacy"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="region" class="block text-sm text-gray-300 mb-1">Region</label>
                <select name="region" id="region" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                    <option value="">Any Region</option>
                    @foreach(['europe'=>'Europe','north_america'=>'North America','south_america'=>'South America','asia'=>'Asia','oceania'=>'Oceania'] as $k=>$v)<option value="{{ $k }}" {{ old('region')===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="scheduled_at" class="block text-sm text-gray-300 mb-1">Scheduled Time</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                <p class="text-gray-500 text-xs mt-1">Leave empty for ASAP</p>
            </div>
        </div>
        <div>
            <label for="contact_discord" class="block text-sm text-gray-300 mb-1">Discord Contact</label>
            <input type="text" name="contact_discord" id="contact_discord" value="{{ old('contact_discord') }}" placeholder="e.g. username or Discord invite"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
        </div>
        @if($clans->count())
        <div>
            <label for="clan_id" class="block text-sm text-gray-300 mb-1">Representing Clan</label>
            <select name="clan_id" id="clan_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                <option value="">No clan / Solo</option>
                @foreach($clans as $c)<option value="{{ $c->id }}" {{ old('clan_id')==$c->id?'selected':'' }}>[{{ $c->tag_clean }}] {{ $c->name ?? '' }}</option>@endforeach
            </select>
        </div>
        @endif
        <div class="flex gap-3 pt-4">
            <button type="submit" class="px-6 py-2.5 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-500 transition">Create Match</button>
            <a href="{{ route('tracker.scrims') }}" class="px-6 py-2.5 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition">Cancel</a>
        </div>
    </form>
</div>
</x-layouts.app>
