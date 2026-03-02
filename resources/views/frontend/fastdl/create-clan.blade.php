<x-layouts.app title="Create Fast Download">
<div class="max-w-2xl mx-auto px-4 py-12">

    <div class="text-center mb-8">
        <div class="text-6xl mb-4">🚀</div>
        <h1 class="text-3xl font-bold text-white">Create your Fast Download</h1>
        <p class="text-gray-400 mt-2">Set up a fast download server for your clan in seconds!</p>
    </div>

    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-8">
        <form action="{{ route('clan.fastdl.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Clan Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-amber-500 focus:ring-amber-500"
                        placeholder="My Awesome Clan">
                    @error('name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">URL Slug</label>
                    <div class="flex items-center">
                        <span class="text-gray-500 text-sm mr-2">dl.wolffiles.eu/</span>
                        <input type="text" name="slug" value="{{ old('slug') }}" required
                            class="flex-1 bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-amber-500 focus:ring-amber-500"
                            placeholder="myclan" pattern="[a-z0-9\-_]+" title="Only lowercase letters, numbers, dashes and underscores">
                        <span class="text-gray-500 text-sm ml-2">/</span>
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Only lowercase letters, numbers, dashes and underscores. This will be your server's download URL.</p>
                    @error('slug') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Game</label>
                    <select name="game_id" required
                        class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-amber-500 focus:ring-amber-500">
                        @foreach($games as $game)
                        <option value="{{ $game->id }}" {{ old('game_id') == $game->id ? 'selected' : '' }}>
                            {{ $game->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('game_id') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="bg-gray-900/50 border border-gray-700 rounded-lg p-4">
                    <h4 class="text-white font-medium mb-2">📋 What you get:</h4>
                    <ul class="text-gray-400 text-sm space-y-1">
                        <li>✅ All maps automatically included</li>
                        <li>✅ Choose which mod directories to include</li>
                        <li>✅ Upload your own custom PK3 files</li>
                        <li>✅ 500 MB storage for custom files</li>
                        <li>✅ Ready-to-use server config</li>
                    </ul>
                </div>

                <button type="submit" class="w-full py-3 rounded-lg font-bold text-white text-lg" style="background:linear-gradient(to right,#f59e0b,#ea580c);">
                    🚀 Create Fast Download
                </button>
            </div>
        </form>
    </div>

    @if(session('error'))
    <div class="fixed bottom-4 right-4 bg-red-900/90 border border-red-700 text-red-300 px-6 py-3 rounded-xl shadow-lg" x-data x-init="setTimeout(() => $el.remove(), 3000)">{{ session('error') }}</div>
    @endif
</div>
</x-layouts.app>
