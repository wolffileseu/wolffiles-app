{{-- Cookie Consent Banner - Fixed bottom center --}}
<div x-data="{ show: !localStorage.getItem('cookie_consent') }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-full opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="translate-y-full opacity-0"
     x-cloak
     style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999;">
    <div style="max-width: 800px; margin: 0 auto 1rem auto; padding: 0 1rem;">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-gray-300 text-sm text-center sm:text-left">
                {{ __('messages.cookie_text') }}
                <a href="/page/privacy-policy" class="text-amber-400 hover:underline">{{ __('messages.privacy_policy') }}</a>.
            </p>
            <div class="flex space-x-3 flex-shrink-0">
                <button @click="localStorage.setItem('cookie_consent', 'accepted'); show = false"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                    {{ __('messages.accept') }}
                </button>
                <button @click="localStorage.setItem('cookie_consent', 'essential'); show = false"
                        class="border border-gray-600 hover:border-gray-500 text-gray-300 px-6 py-2 rounded-lg text-sm transition-colors whitespace-nowrap">
                    {{ __('messages.essential_only') }}
                </button>
            </div>
        </div>
    </div>
</div>
