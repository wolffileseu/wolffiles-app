<x-layouts.app title="Settings">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">Account Settings</h1>

        {{-- Profile Settings --}}
        <form method="POST" action="{{ route('profile.settings.update') }}" class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
            @csrf @method('PUT')
            <h2 class="text-xl font-semibold text-white mb-6">Profile</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Bio</label>
                    <textarea name="bio" rows="3" maxlength="1000"
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">{{ old('bio', auth()->user()->bio) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Website</label>
                    <input type="url" name="website" value="{{ old('website', auth()->user()->website) }}" placeholder="https://"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Language</label>
                    <select name="locale" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                        @foreach(config('languages', []) as $code => $lang)
                            @if(is_dir(lang_path($code)))
                            <option value="{{ $code }}" {{ auth()->user()->locale === $code ? 'selected' : '' }}>{{ $lang['flag'] }} {{ $lang['name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="mt-6 bg-amber-600 hover:bg-amber-700 text-white px-8 py-2 rounded-lg font-medium transition-colors">
                Save Changes
            </button>
        </form>

        {{-- Password Change --}}
        <form method="POST" action="{{ route('password.update') }}" class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
            @csrf @method('PUT')
            <h2 class="text-xl font-semibold text-white mb-6">Change Password</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Current Password</label>
                    <input type="password" name="current_password" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('current_password') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">New Password</label>
                    <input type="password" name="password" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('password') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>

            <button type="submit" class="mt-6 bg-amber-600 hover:bg-amber-700 text-white px-8 py-2 rounded-lg font-medium transition-colors">
                Update Password
            </button>
        </form>

        {{-- Discord Connection --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Discord</h2>
            @if(auth()->user()->discord_id)
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                        <span class="text-gray-300">Connected as <strong class="text-indigo-400">{{ auth()->user()->discord_username }}</strong></span>
                    </div>
                    <a href="{{ route('auth.discord.disconnect') }}" class="text-red-400 hover:text-red-300 text-sm">Disconnect</a>
                </div>
            @else
                <a href="{{ route('auth.discord.redirect') }}"
                   class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                    <span>Connect Discord</span>
                </a>
            @endif
        </div>
    </div>
</x-layouts.app>
