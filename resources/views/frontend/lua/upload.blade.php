<x-layouts.app title="{{ __('messages.upload_lua') }}">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-2">{{ __('messages.upload_lua') }}</h1>
        <p class="text-gray-400 mb-8">{{ __('messages.upload_lua_desc') }}</p>

        <form method="POST" action="{{ route('lua.store') }}" enctype="multipart/form-data"
              class="bg-gray-800 rounded-lg border border-gray-700 p-8 space-y-6">
            @csrf

            {{-- Honeypot --}}
            <div class="hidden">
                <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.title') }} *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                @error('title') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.description') }}</label>
                <textarea name="description" rows="5"
                          class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.category') }} *</label>
                    <select name="category_id" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                        <option value="">{{ __('messages.select') }}...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.version') }}</label>
                    <input type="text" name="version" value="{{ old('version') }}" placeholder="z.B. 1.0"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.compatible_mods') }}</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach(['etpub' => 'ETPub', 'silent' => 'Silent Mod', 'nitmod' => 'N!tmod', 'legacy' => 'ET: Legacy', 'jaymod' => 'Jaymod', 'etjump' => 'ETJump'] as $val => $label)
                        <label class="flex items-center space-x-2 text-sm text-gray-300">
                            <input type="checkbox" name="compatible_mods[]" value="{{ $val }}"
                                   class="rounded bg-gray-700 border-gray-600 text-amber-600 focus:ring-amber-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.lua_file') }} *</label>
                <input type="file" name="file" required accept=".lua,.zip,.pk3"
                       class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-amber-600 file:text-white file:cursor-pointer">
                <p class="text-gray-500 text-sm mt-1">{{ __('messages.lua_file_types') }}</p>
                @error('file') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.installation_guide') }}</label>
                <textarea name="installation_guide" rows="4"
                          class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3"
                          placeholder="{{ __('messages.installation_guide_placeholder') }}">{{ old('installation_guide') }}</textarea>
            </div>

            <div class="bg-gray-750 rounded-lg p-4 border border-gray-600">
                <p class="text-gray-400 text-sm">
                    {{ __('messages.upload_review_info') }}
                </p>
            </div>

            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-4 rounded-lg font-bold text-lg transition-colors">
                {{ __('messages.upload_lua') }}
            </button>
        </form>
    </div>
</x-layouts.app>
