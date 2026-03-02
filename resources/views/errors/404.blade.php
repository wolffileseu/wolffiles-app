<x-layouts.app title="404 - Lost Behind Enemy Lines">
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center px-4">
            <div class="text-8xl font-black text-red-600 mb-4" style="font-family: 'Impact', 'Arial Black', sans-serif; text-shadow: 3px 3px 0 #000;">
                404
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">
                {{ __('messages.error_404_title') }}
            </h1>
            <p class="text-gray-400 text-lg mb-8">
                {{ __('messages.error_404_text') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('home') }}"
                   class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    {{ __('messages.back_to_base') }}
                </a>
                <a href="{{ route('files.index') }}"
                   class="text-gray-400 hover:text-amber-400 transition-colors">
                    {{ __('messages.browse_files') }}
                </a>
            </div>
            <div class="mt-12 text-gray-600 text-sm italic">
                "{{ __('messages.error_404_quote') }}"
            </div>
        </div>
    </div>
</x-layouts.app>
