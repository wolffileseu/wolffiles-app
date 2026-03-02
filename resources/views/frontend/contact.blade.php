<x-layouts.app title="Contact">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-2">{{ __('messages.contact') }}</h1>
        <p class="text-gray-400 mb-8">{{ __('messages.contact_desc') }}</p>

        <form method="POST" action="{{ route('contact.send') }}" class="bg-gray-800 rounded-lg border border-gray-700 p-8 space-y-6">
            @csrf

            {{-- Honeypot --}}
            <div class="hidden">
                <input type="text" name="honeypot" value="" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('messages.name') }} *</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('messages.email') }} *</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('email') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('messages.subject') }} *</label>
                <select name="subject" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                    <option value="">{{ __('messages.select') }}...</option>
                    <option value="General Question" {{ old('subject') == 'General Question' ? 'selected' : '' }}>{{ __('messages.general_question') }}</option>
                    <option value="File Request" {{ old('subject') == 'File Request' ? 'selected' : '' }}>{{ __('messages.file_request') }}</option>
                    <option value="Bug Report" {{ old('subject') == 'Bug Report' ? 'selected' : '' }}>{{ __('messages.bug_report') }}</option>
                    <option value="Copyright Issue" {{ old('subject') == 'Copyright Issue' ? 'selected' : '' }}>{{ __('messages.copyright_issue') }}</option>
                    <option value="Partnership" {{ old('subject') == 'Partnership' ? 'selected' : '' }}>{{ __('messages.partnership') }}</option>
                    <option value="Other" {{ old('subject') == 'Other' ? 'selected' : '' }}>{{ __('messages.other') }}</option>
                </select>
                @error('subject') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('messages.message') }} *</label>
                <textarea name="message" rows="6" required maxlength="5000"
                          class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 focus:ring-amber-500 focus:border-amber-500">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-bold text-lg transition-colors">
                {{ __('messages.send') }}
            </button>
        </form>

        <div class="mt-8 text-center text-gray-500 text-sm">
            {{ __('messages.contact_alternative') }}
            <a href="https://discord.com/invite/wzkRyWWuxP" target="_blank" class="text-indigo-400 hover:underline">Discord</a>
        </div>
    </div>
</x-layouts.app>
