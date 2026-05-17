<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-2">
            <x-filament::button type="submit" color="primary">
                Einstellungen speichern
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
