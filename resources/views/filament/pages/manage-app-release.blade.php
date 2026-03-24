<x-filament-panels::page>
    <form wire:submit="save">
        <div class="space-y-6">
            <x-filament::section heading="ClanNewsTool Release">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Version</label>
                        <input
                            type="text"
                            wire:model="version"
                            placeholder="1.0.0"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Changelog</label>
                        <textarea
                            wire:model="changelog"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"
                            placeholder="Was ist neu in dieser Version?"
                        ></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="force_update" id="force_update" class="rounded">
                        <label for="force_update" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Force Update (User werden gezwungen zu updaten)
                        </label>
                    </div>
                    <div class="pt-2">
                        <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">
                            Release speichern
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </form>
</x-filament-panels::page>
