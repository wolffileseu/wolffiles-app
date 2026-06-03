@props(['target' => 'editor'])

@php
$buttons = \App\Helpers\BBCode::getToolbarButtons();
$colors = ['white', 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink', 'gray'];
$sizes = [10, 12, 14, 16, 18, 20, 24, 28];
@endphp

<div x-data="bbcodeToolbar('{{ $target }}')"
     class="flex flex-wrap items-center gap-1 px-2 py-2 bg-gray-900 border border-gray-700 rounded-t-lg border-b-0">
    @foreach($buttons as $btn)
        @if(isset($btn['picker']) && $btn['picker'] === 'color')
            {{-- Color picker dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        class="px-2.5 py-1.5 text-gray-300 hover:bg-gray-800 hover:text-amber-400 rounded transition text-sm"
                        title="{{ $btn['title'] }}">
                    <i class="{{ $btn['icon'] }}"></i>
                </button>
                <div x-show="open" x-cloak
                     class="absolute z-30 top-full left-0 mt-1 bg-gray-900 border border-gray-700 rounded-lg shadow-xl p-2 grid grid-cols-3 gap-1 min-w-[140px]">
                    @foreach($colors as $color)
                        <button type="button"
                                @click="wrap('color={{ $color }}', 'color'); open = false"
                                class="w-9 h-9 rounded border border-gray-700 hover:border-amber-400 transition"
                                style="background-color: {{ $color }};"
                                title="{{ $color }}"></button>
                    @endforeach
                </div>
            </div>
        @elseif(isset($btn['picker']) && $btn['picker'] === 'size')
            {{-- Size picker dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        class="px-2.5 py-1.5 text-gray-300 hover:bg-gray-800 hover:text-amber-400 rounded transition text-sm"
                        title="{{ $btn['title'] }}">
                    <i class="{{ $btn['icon'] }}"></i>
                </button>
                <div x-show="open" x-cloak
                     class="absolute z-30 top-full left-0 mt-1 bg-gray-900 border border-gray-700 rounded-lg shadow-xl py-1 min-w-[80px]">
                    @foreach($sizes as $size)
                        <button type="button"
                                @click="wrap('size={{ $size }}', 'size'); open = false"
                                class="block w-full px-3 py-1 text-left text-gray-300 hover:bg-gray-800 hover:text-amber-400 transition"
                                style="font-size: {{ $size }}px;">{{ $size }}px</button>
                    @endforeach
                </div>
            </div>
        @elseif(!empty($btn['selfClosing']))
            <button type="button"
                    @click="insert('[{{ $btn['tag'] }}]')"
                    class="px-2.5 py-1.5 text-gray-300 hover:bg-gray-800 hover:text-amber-400 rounded transition text-sm"
                    title="{{ $btn['title'] }}">
                <i class="{{ $btn['icon'] }}"></i>
            </button>
        @else
            <button type="button"
                    @click="wrap('{{ $btn['tag'] }}', '{{ $btn['tag'] }}')"
                    class="px-2.5 py-1.5 text-gray-300 hover:bg-gray-800 hover:text-amber-400 rounded transition text-sm"
                    title="{{ $btn['title'] }}">
                <i class="{{ $btn['icon'] }}"></i>
            </button>
        @endif
    @endforeach
</div>

@once
<script>
function bbcodeToolbar(targetId) {
    return {
        getEl() {
            return document.getElementById(targetId);
        },
        insert(text) {
            const el = this.getEl();
            if (!el) return;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            const value = el.value;
            el.value = value.substring(0, start) + text + value.substring(end);
            el.focus();
            el.selectionStart = el.selectionEnd = start + text.length;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        },
        wrap(openTag, closeTag) {
            const el = this.getEl();
            if (!el) return;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            const value = el.value;
            const selected = value.substring(start, end);
            const before = '[' + openTag + ']';
            const after = '[/' + closeTag + ']';
            const replacement = before + selected + after;
            el.value = value.substring(0, start) + replacement + value.substring(end);
            el.focus();
            // Place cursor inside the wrapped tag if no selection, else after it
            if (selected.length === 0) {
                el.selectionStart = el.selectionEnd = start + before.length;
            } else {
                el.selectionStart = el.selectionEnd = start + replacement.length;
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };
}
</script>
@endonce
