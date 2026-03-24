<div class="max-w-2xl mx-auto py-10 space-y-8">

    {{-- Datenexport --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('messages.data_export_title') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.data_export_description') }}</p>

        @if(session('export_info'))
            <div class="text-sm text-blue-600 bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3">{{ session('export_info') }}</div>
        @endif

        @if($exportReady)
            <a href="{{ route('settings.privacy.download') }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                {{ __('messages.download_export') }}
            </a>
        @elseif($exportRequested)
            <p class="text-sm text-gray-500 italic">{{ __('messages.export_being_prepared') }}</p>
        @else
            <button wire:click="requestExport" wire:loading.attr="disabled" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                <span wire:loading.remove wire:target="requestExport">{{ __('messages.request_export') }}</span>
                <span wire:loading wire:target="requestExport">...</span>
            </a>
        @endif
    </div>

    {{-- Account loeschen --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4 border border-red-200 dark:border-red-800">
        <h2 class="text-lg font-semibold text-red-600 dark:text-red-400">{{ __('messages.delete_account_title') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.delete_account_description') }}</p>
        <ul class="text-sm text-gray-500 list-disc list-inside space-y-1">
            <li>{{ __('messages.delete_keeps_uploads') }}</li>
            <li>{{ __('messages.delete_removes_profile') }}</li>
            <li>{{ __('messages.delete_removes_messages') }}</li>
        </ul>
        <button wire:click="$set('showDeleteModal', true)" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
            {{ __('messages.delete_account_button') }}
        </a>
    </div>

    {{-- Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-8 max-w-md w-full mx-4 space-y-5">
            <h3 class="text-lg font-bold text-red-600">{{ __('messages.confirm_delete_title') }}</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ __('messages.confirm_delete_instruction') }}
                <strong class="font-mono bg-gray-100 dark:bg-gray-800 px-1 rounded">LOESCHEN</strong>
            </p>
            <input wire:model="deleteConfirm" type="text" placeholder="LOESCHEN"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500" />
            @error("deleteConfirm")
                <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
            <div class="flex gap-3 justify-end">
                <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    {{ __('messages.cancel') }}
                </a>
                <button wire:click="deleteAccount" wire:loading.attr="disabled" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    <span wire:loading.remove wire:target="deleteAccount">{{ __('messages.delete_account_confirm') }}</span>
                    <span wire:loading wire:target="deleteAccount">...</span>
                </a>
            </div>
        </div>
    </div>
    @endif

</div>
