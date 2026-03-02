<x-layouts.app :title="'Claim Clan'">
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.clan.show', $clan) }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Clan</a>

    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <h1 class="text-2xl font-bold text-amber-500 mb-2">Claim Clan</h1>
        <div class="flex items-center gap-3 bg-gray-700/50 rounded-lg p-4">
            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center text-amber-400 font-bold text-sm border border-amber-500/30">
                {{ strtoupper(substr($clan->tag_clean, 0, 3)) }}
            </div>
            <div>
                <span class="text-white font-semibold">[{{ $clan->tag_clean }}] {{ $clan->name ?? '' }}</span>
                <div class="text-gray-400 text-xs">{{ $clan->member_count }} members</div>
            </div>
        </div>
        <p class="text-gray-400 text-sm mt-3">
            As clan owner, you can manage the clan page, add a description, website, and other details.
            A moderator will review your claim before it's approved.
        </p>
    </div>

    <form method="POST" action="{{ route('tracker.claim.clan.store', $clan) }}" class="space-y-6">
        @csrf

        {{-- Proof Section --}}
        <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
            <h2 class="text-white font-semibold mb-4">Verification</h2>

            <div class="mb-4">
                <label for="proof_type" class="block text-sm text-gray-300 mb-1">Your role *</label>
                <select name="proof_type" id="proof_type" required
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                    <option value="">Select...</option>
                    <option value="clan_leader" {{ old('proof_type') === 'clan_leader' ? 'selected' : '' }}>I am the clan leader/founder</option>
                    <option value="clan_member" {{ old('proof_type') === 'clan_member' ? 'selected' : '' }}>I am an authorized clan officer</option>
                    <option value="server_admin" {{ old('proof_type') === 'server_admin' ? 'selected' : '' }}>I run the clan's server</option>
                    <option value="other" {{ old('proof_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('proof_type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="message" class="block text-sm text-gray-300 mb-1">Proof / Message to moderators *</label>
                <textarea name="message" id="message" rows="4" required minlength="10"
                          placeholder="Explain your connection to this clan. Include any proof such as server ownership, clan forum links, Discord server admin status..."
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Clan Details Section --}}
        <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
            <h2 class="text-white font-semibold mb-1">Clan Details</h2>
            <p class="text-gray-500 text-xs mb-4">Optional — these will be shown on your clan page after approval.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="clan_email" class="block text-sm text-gray-300 mb-1">Contact Email</label>
                    <input type="email" name="clan_email" id="clan_email" value="{{ old('clan_email', $clan->clan_email) }}"
                           placeholder="clan@example.com"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
                    @error('clan_email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="clan_discord" class="block text-sm text-gray-300 mb-1">Discord</label>
                    <input type="text" name="clan_discord" id="clan_discord" value="{{ old('clan_discord', $clan->discord) }}"
                           placeholder="https://discord.gg/... or Username#1234"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
                    @error('clan_discord') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="clan_website" class="block text-sm text-gray-300 mb-1">Website</label>
                <input type="url" name="clan_website" id="clan_website" value="{{ old('clan_website', $clan->website) }}"
                       placeholder="https://www.yourclan.com"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
                @error('clan_website') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="clan_description" class="block text-sm text-gray-300 mb-1">Clan Description</label>
                <textarea name="clan_description" id="clan_description" rows="3"
                          placeholder="Tell the community about your clan, its history, what games you play..."
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('clan_description', $clan->description) }}</textarea>
                @error('clan_description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit" class="px-6 py-2.5 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-500 transition">
                Submit Clan Claim
            </button>
            <a href="{{ route('tracker.clan.show', $clan) }}" class="px-6 py-2.5 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-layouts.app>
