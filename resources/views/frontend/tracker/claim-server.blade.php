<x-layouts.app :title="'Claim Server'">
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.server.show', $server) }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Server</a>

    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <h1 class="text-2xl font-bold text-amber-500 mb-2">Claim Server</h1>
        <div class="flex items-center gap-3 bg-gray-700/50 rounded-lg p-4">
            <span class="inline-block w-3 h-3 rounded-full {{ $server->is_online ? 'bg-green-500' : 'bg-red-500' }}"></span>
            <div>
                <span class="text-white font-semibold">{!! $server->hostname_html ?: e($server->hostname_clean ?: $server->full_address) !!}</span>
                <div class="text-gray-400 text-xs">{{ $server->ip }}:{{ $server->port }}</div>
            </div>
            <span class="text-gray-400 text-sm ml-auto">{{ $server->current_players }}/{{ $server->max_players }}</span>
        </div>
        <p class="text-gray-400 text-sm mt-3">
            As server owner, you can add a description, website, Discord link, and manage your server's page.
            A moderator will review your claim before it's approved.
        </p>
    </div>

    <form method="POST" action="{{ route('tracker.claim.server.store', $server) }}" class="space-y-6">
        @csrf

        {{-- Proof Section --}}
        <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
            <h2 class="text-white font-semibold mb-4">Verification</h2>

            <div class="mb-4">
                <label for="proof_type" class="block text-sm text-gray-300 mb-1">How can you prove ownership? *</label>
                <select name="proof_type" id="proof_type" required
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-amber-500">
                    <option value="">Select...</option>
                    <option value="server_admin" {{ old('proof_type') === 'server_admin' ? 'selected' : '' }}>I have RCON / admin access</option>
                    <option value="server_hoster" {{ old('proof_type') === 'server_hoster' ? 'selected' : '' }}>I pay for / host this server</option>
                    <option value="ip_owner" {{ old('proof_type') === 'ip_owner' ? 'selected' : '' }}>I own this IP address</option>
                    <option value="other" {{ old('proof_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('proof_type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="message" class="block text-sm text-gray-300 mb-1">Proof / Message to moderators *</label>
                <textarea name="message" id="message" rows="4" required minlength="10"
                          placeholder="Explain how you can prove ownership. For example: I can change the server name, I have RCON access, this server runs on my dedicated machine..."
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Server Details Section --}}
        <div class="bg-gray-800/50 rounded-lg p-5 border border-gray-700">
            <h2 class="text-white font-semibold mb-1">Server Details</h2>
            <p class="text-gray-500 text-xs mb-4">Optional — shown on your server page after approval.</p>

            <div class="mb-4">
                <label for="server_description" class="block text-sm text-gray-300 mb-1">Server Description</label>
                <textarea name="server_description" id="server_description" rows="3"
                          placeholder="Tell players about your server: rules, mods, community, events..."
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('server_description', $server->description) }}</textarea>
                @error('server_description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="server_website" class="block text-sm text-gray-300 mb-1">Website</label>
                    <input type="url" name="server_website" id="server_website" value="{{ old('server_website', $server->server_website) }}"
                           placeholder="https://www.yourserver.com"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
                    @error('server_website') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="server_discord" class="block text-sm text-gray-300 mb-1">Discord</label>
                    <input type="text" name="server_discord" id="server_discord" value="{{ old('server_discord', $server->server_discord) }}"
                           placeholder="https://discord.gg/... or Username#1234"
                           class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
                    @error('server_discord') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="server_email" class="block text-sm text-gray-300 mb-1">Contact Email</label>
                <input type="email" name="server_email" id="server_email" value="{{ old('server_email', $server->server_email) }}"
                       placeholder="admin@yourserver.com"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-amber-500">
                @error('server_email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit" class="px-6 py-2.5 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-500 transition">
                Submit Server Claim
            </button>
            <a href="{{ route('tracker.server.show', $server) }}" class="px-6 py-2.5 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-layouts.app>
