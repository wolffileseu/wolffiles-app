<x-layouts.app :title="'Claim Player Profile'">
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Player</a>

    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <h1 class="text-2xl font-bold text-amber-500 mb-2">Claim Player Profile</h1>
        <div class="flex items-center gap-3 bg-gray-700/50 rounded-lg p-4">
            @if($player->country_code)
            <img src="https://flagcdn.com/{{ strtolower($player->country_code) }}.svg" class="w-5 h-3 rounded-sm" alt="">
            @endif
            <span class="text-white font-semibold">{!! $player->name_html ?? e($player->name_clean ?? 'Unknown') !!}</span>
            <span class="text-gray-400 text-sm ml-auto">ELO {{ number_format($player->elo_rating) }}</span>
        </div>
        <p class="text-gray-400 text-sm mt-3">
            By claiming this profile, you confirm that this is your in-game player.
            A moderator will review your claim. Once approved, this profile will be linked to your account.
        </p>
    </div>

    <form method="POST" action="{{ route('tracker.claim.player.store', $player) }}" class="space-y-6">
        @csrf

        <div>
            <label for="proof_type" class="block text-sm text-gray-300 mb-1">How can you prove this is you? *</label>
            <select name="proof_type" id="proof_type" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                <option value="">Select...</option>
                <option value="guid" {{ old('proof_type') === 'guid' ? 'selected' : '' }}>I know my ET GUID</option>
                <option value="screenshot" {{ old('proof_type') === 'screenshot' ? 'selected' : '' }}>I can provide a screenshot</option>
                <option value="server_admin" {{ old('proof_type') === 'server_admin' ? 'selected' : '' }}>I am a server admin</option>
                <option value="known_player" {{ old('proof_type') === 'known_player' ? 'selected' : '' }}>I am a well-known community member</option>
                <option value="other" {{ old('proof_type') === 'other' ? 'selected' : '' }}>Other proof</option>
            </select>
            @error('proof_type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="message" class="block text-sm text-gray-300 mb-1">Your message to the moderators *</label>
            <textarea name="message" id="message" rows="4" required minlength="10"
                      placeholder="Explain why this is your player profile. Include any proof such as your GUID, clan membership, or other identifying details..."
                      class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('message') }}</textarea>
            @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            <p class="text-gray-500 text-xs mt-1">Minimum 10 characters. The more detail you provide, the faster we can verify.</p>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit" class="px-6 py-2.5 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-500 transition">
                Submit Claim
            </button>
            <a href="{{ route('tracker.player.show', $player) }}" class="px-6 py-2.5 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-layouts.app>
