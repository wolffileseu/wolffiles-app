<x-layouts.app :title="__('messages.login')">
    <div class="max-w-md mx-auto px-4 py-12">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8">
            <h2 class="text-2xl font-bold text-white text-center mb-6">{{ __('messages.login') }}</h2>

            @if(session('error'))
                <div class="bg-red-900/50 border border-red-700 text-red-200 px-4 py-3 rounded-lg mb-6 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1">{{ __('messages.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                    @error('email') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1">{{ __('messages.password') }}</label>
                    <input type="password" name="password" id="password" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                    @error('password') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center text-sm text-gray-300">
                        <input type="checkbox" name="remember" class="rounded bg-gray-700 border-gray-600 text-amber-600 mr-2">
                        {{ __('messages.remember_me') }}
                    </label>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-amber-400 hover:underline">{{ __('messages.forgot_password') }}</a>
                    @endif
                </div>

                <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-medium transition-colors mb-4">
                    {{ __('messages.login') }}
                </button>
            </form>

            {{-- Discord Login --}}
            @if(config('services.discord.client_id'))
                <div class="relative my-4">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-700"></div></div>
                    <div class="relative flex justify-center text-sm"><span class="px-3 bg-gray-800 text-gray-400">{{ __('messages.or') }}</span></div>
                </div>

                <a href="{{ route('auth.discord.redirect') }}"
                   class="w-full flex items-center justify-center space-x-2 bg-[#5865F2] hover:bg-[#4752C4] text-white py-3 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                    <span>{{ __('messages.login_discord') }}</span>
                </a>
            @endif

            <p class="text-center text-gray-400 text-sm mt-6">
                {{ __('messages.no_account') }}
                <a href="{{ route('register') }}" class="text-amber-400 hover:underline">{{ __('messages.register') }}</a>
            </p>
        </div>
    </div>
</x-layouts.app>
