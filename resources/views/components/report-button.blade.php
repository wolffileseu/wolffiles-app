{{-- Melden/Report Button + Modal --}}
{{-- Usage: @include('components.report-button', ['type' => 'App\Models\File', 'id' => $file->id]) --}}

@auth
<div x-data="{ reportOpen: false }">
    <button @click="reportOpen = true"
            class="inline-flex items-center space-x-1 text-gray-500 hover:text-red-400 text-sm transition-colors"
            title="{{ __('messages.report') }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
        </svg>
        <span>{{ __('messages.report') }}</span>
    </button>

    {{-- Modal --}}
    <div x-show="reportOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         @click.self="reportOpen = false"
         @keydown.escape.window="reportOpen = false">
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 w-full max-w-md mx-4"
             x-transition>
            <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.report_title') }}</h3>

            <form method="POST" action="{{ route('reports.store') }}">
                @csrf
                <input type="hidden" name="reportable_type" value="{{ $type }}">
                <input type="hidden" name="reportable_id" value="{{ $id }}">

                {{-- Honeypot (hidden spam field) --}}
                <div class="hidden">
                    <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.report_reason') }}</label>
                    <select name="reason" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                        <option value="">{{ __('messages.select') }}...</option>
                        <option value="copyright">{{ __('messages.report_copyright') }}</option>
                        <option value="broken">{{ __('messages.report_broken') }}</option>
                        <option value="inappropriate">{{ __('messages.report_inappropriate') }}</option>
                        <option value="spam">{{ __('messages.report_spam') }}</option>
                        <option value="other">{{ __('messages.report_other') }}</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.report_description') }}</label>
                    <textarea name="description" rows="3" maxlength="1000"
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm"
                              placeholder="{{ __('messages.report_description_placeholder') }}"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" @click="reportOpen = false"
                            class="px-4 py-2 text-gray-400 hover:text-white text-sm transition-colors">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                        {{ __('messages.report_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endauth
