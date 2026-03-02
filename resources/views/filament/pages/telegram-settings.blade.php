<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::section>
            <x-slot name="heading">
                🤖 Telegram Bot Settings
            </x-slot>

            <div class="space-y-4">
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                        <input type="checkbox" wire:model="enabled"
                            class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600">
                        <span class="text-sm font-medium">Enable Telegram Notifications</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bot Token</label>
                    <input type="text" wire:model="bot_token"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="123456:ABC-DEF...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chat ID</label>
                    <input type="text" wire:model="chat_id"
                        class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="707894553">
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="mt-4">
            <x-slot name="heading">
                📋 Notification Events
            </x-slot>
            <x-slot name="description">
                Select which events should send Telegram notifications
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach(\App\Filament\Pages\TelegramSettings::$availableEvents as $key => $label)
                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                        <input type="checkbox" value="{{ $key }}" wire:model="events"
                            class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600">
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </x-filament::section>

        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit">
                💾 Save Settings
            </x-filament::button>

            <x-filament::button color="gray" wire:click="testConnection" type="button">
                🧪 Send Test Message
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>