@php($statePath = $getStatePath())
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="wikitextEditor({ state: $wire.$entangle('{{ $statePath }}') })"
        wire:ignore
        style="border:1px solid rgba(255,255,255,0.1);border-radius:8px;background:rgba(0,0,0,0.2);overflow:hidden;"
    >
        <div x-ref="editor" style="min-height:360px;max-height:70vh;overflow:auto;"></div>
    </div>
</x-dynamic-component>
