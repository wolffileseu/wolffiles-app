<x-layouts.app :title="__('Report Player')">
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; {{ __('Back to profile') }}</a>

    <div class="bg-gray-800 rounded-lg p-4 sm:p-6 mt-4">
        <h1 class="text-xl font-bold text-white mb-1 flex items-center gap-2">&#9888; {{ __('Report Player') }}</h1>
        <p class="text-gray-400 text-sm mb-5">
            {{ __('Reporting') }}: <span class="text-amber-400 font-semibold">{!! $player->name_html ?: e($player->name_clean ?: 'Unknown') !!}</span>
        </p>

        @if(session('error'))
            <div class="mb-4 p-3 rounded bg-red-900/40 border border-red-500/40 text-red-200 text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-4 p-3 rounded bg-amber-900/20 border border-amber-500/30 text-amber-200/90 text-xs">
            {{ __('Please only report genuine cheating and provide evidence. False or malicious reports may lead to action against your account. All reports are reviewed manually.') }}
        </div>

        <form method="POST" action="{{ route('tracker.player.report.store', $player) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- Honeypot --}}
            <input type="text" name="website_url" value="" class="hidden" tabindex="-1" autocomplete="off">

            <div>
                <label class="block text-sm text-gray-300 mb-1">{{ __('What did you observe?') }} <span class="text-red-400">*</span></label>
                <textarea name="description" rows="5" required minlength="10" maxlength="1000"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-gray-200 text-sm focus:border-amber-500 focus:outline-none"
                    placeholder="{{ __('Describe what happened (aimbot, wallhack, suspicious behaviour, which map/round, etc.)') }}">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">{{ __('Player GUID') }} <span class="text-gray-500">({{ __('optional') }})</span></label>
                <input type="text" name="reported_guid" value="{{ old('reported_guid') }}" maxlength="64"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-gray-200 text-sm font-mono focus:border-amber-500 focus:outline-none"
                    placeholder="{{ __('If you know it (hexadecimal)') }}">
                @error('reported_guid')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">{{ __('Your contact') }} <span class="text-gray-500">({{ __('optional, e.g. Discord') }})</span></label>
                <input type="text" name="contact" value="{{ old('contact') }}" maxlength="255"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-gray-200 text-sm focus:border-amber-500 focus:outline-none"
                    placeholder="{{ __('So we can follow up if needed') }}">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">{{ __('Screenshots') }} <span class="text-gray-500">({{ __('optional, up to 5, max 10MB each') }})</span></label>
                <input type="file" name="screenshots[]" multiple accept="image/*"
                    class="w-full text-gray-400 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-amber-500 file:text-gray-900 file:font-semibold file:text-xs hover:file:bg-amber-400">
                @error('screenshots.*')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-semibold rounded-lg text-sm transition">
                    {{ __('Submit Report') }}
                </button>
                <a href="{{ route('tracker.player.show', $player) }}" class="text-gray-400 hover:text-gray-300 text-sm">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
</x-layouts.app>
