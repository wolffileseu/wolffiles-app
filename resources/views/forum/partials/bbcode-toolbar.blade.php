<div x-data="bbcodeEditor()" class="bbcode-editor">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-1 p-2 bg-gray-900 border border-gray-700 rounded-t-lg">
        <button type="button" @click="insert('b')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Bold">
            <i class="fas fa-bold"></i>
        </button>
        <button type="button" @click="insert('i')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Italic">
            <i class="fas fa-italic"></i>
        </button>
        <button type="button" @click="insert('u')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Underline">
            <i class="fas fa-underline"></i>
        </button>
        <button type="button" @click="insert('s')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Strikethrough">
            <i class="fas fa-strikethrough"></i>
        </button>
        <span class="border-l border-gray-700 mx-1 h-5"></span>
        <button type="button" @click="insertUrl()" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Link">
            <i class="fas fa-link"></i>
        </button>
        <button type="button" @click="insertImg()" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Image">
            <i class="fas fa-image"></i>
        </button>
        <button type="button" @click="insert('code')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Code">
            <i class="fas fa-code"></i>
        </button>
        <button type="button" @click="insert('quote')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Quote">
            <i class="fas fa-quote-left"></i>
        </button>
        <button type="button" @click="insert('spoiler')" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Spoiler">
            <i class="fas fa-eye-slash"></i>
        </button>
        <span class="border-l border-gray-700 mx-1 h-5"></span>
        <button type="button" @click="insertList()" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="List">
            <i class="fas fa-list"></i>
        </button>
        <button type="button" @click="insertYoutube()" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="YouTube">
            <i class="fab fa-youtube"></i>
        </button>
        <button type="button" @click="insertHr()" class="px-2 py-1 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition text-sm" title="Line">
            <i class="fas fa-minus"></i>
        </button>

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Tab Toggle --}}
        <div class="flex bg-gray-800 rounded-lg overflow-hidden border border-gray-700">
            <button type="button" @click="tab = 'write'"
                    :class="tab === 'write' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white'"
                    class="px-3 py-1 text-xs font-medium transition">
                <i class="fas fa-pen mr-1"></i> Write
            </button>
            <button type="button" @click="tab = 'preview'; updatePreview()"
                    :class="tab === 'preview' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white'"
                    class="px-3 py-1 text-xs font-medium transition">
                <i class="fas fa-eye mr-1"></i> Preview
            </button>
            <button type="button" @click="tab = 'split'; updatePreview()"
                    :class="tab === 'split' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white'"
                    class="px-3 py-1 text-xs font-medium transition hidden sm:block">
                <i class="fas fa-columns mr-1"></i> Split
            </button>
        </div>
    </div>

    {{-- Editor Area --}}
    <div class="border border-t-0 border-gray-700 rounded-b-lg overflow-hidden"
         :class="tab === 'split' ? 'grid grid-cols-2 divide-x divide-gray-700' : ''">

        {{-- Textarea --}}
        <div x-show="tab !== 'preview'">
            <textarea x-ref="textarea"
                      name="body"
                      :rows="tab === 'split' ? '12' : '8'"
                      class="w-full bg-gray-900 border-0 px-4 py-3 text-white placeholder-gray-500 focus:ring-0 resize-y font-mono text-sm"
                      :class="tab === 'split' ? 'rounded-bl-lg' : 'rounded-b-lg'"
                      placeholder="{{ __('messages.forum_thread_body_placeholder') }}"
                      required
                      minlength="3"
                      @input="if(tab === 'split') updatePreview()">{{ old('body') }}</textarea>
        </div>

        {{-- Preview --}}
        <div x-show="tab !== 'write'"
             x-ref="preview"
             class="bg-gray-900/50 px-4 py-3 prose prose-invert max-w-none text-gray-300 min-h-[200px] overflow-y-auto"
             :class="tab === 'preview' ? 'rounded-b-lg' : 'rounded-br-lg'">
            <span class="text-gray-600 italic">Preview...</span>
        </div>
    </div>
</div>

<script>
function bbcodeEditor() {
    return {
        tab: 'write',

        getTA() { return this.$refs.textarea; },

        insert(tag) {
            const ta = this.getTA();
            const s = ta.selectionStart, e = ta.selectionEnd;
            const sel = ta.value.substring(s, e);
            const before = ta.value.substring(0, s);
            const after = ta.value.substring(e);
            ta.value = before + '[' + tag + ']' + sel + '[/' + tag + ']' + after;
            ta.focus();
            ta.selectionStart = s + tag.length + 2;
            ta.selectionEnd = s + tag.length + 2 + sel.length;
        },

        insertUrl() {
            const ta = this.getTA();
            const sel = ta.value.substring(ta.selectionStart, ta.selectionEnd);
            const url = prompt('URL:', 'https://');
            if (!url) return;
            const text = sel || prompt('Link Text:', url) || url;
            const s = ta.selectionStart;
            ta.value = ta.value.substring(0, s) + '[url=' + url + ']' + text + '[/url]' + ta.value.substring(ta.selectionEnd);
            ta.focus();
        },

        insertImg() {
            const url = prompt('Image URL:', 'https://');
            if (!url) return;
            const ta = this.getTA();
            const s = ta.selectionStart;
            ta.value = ta.value.substring(0, s) + '[img]' + url + '[/img]' + ta.value.substring(ta.selectionEnd);
            ta.focus();
        },

        insertList() {
            const ta = this.getTA();
            const s = ta.selectionStart;
            ta.value = ta.value.substring(0, s) + '[list]\n[*]Item 1\n[*]Item 2\n[*]Item 3\n[/list]' + ta.value.substring(ta.selectionEnd);
            ta.focus();
        },

        insertYoutube() {
            const url = prompt('YouTube URL:', 'https://www.youtube.com/watch?v=');
            if (!url) return;
            const ta = this.getTA();
            const s = ta.selectionStart;
            ta.value = ta.value.substring(0, s) + '[youtube]' + url + '[/youtube]' + ta.value.substring(ta.selectionEnd);
            ta.focus();
        },

        insertHr() {
            const ta = this.getTA();
            const s = ta.selectionStart;
            ta.value = ta.value.substring(0, s) + '[hr]' + ta.value.substring(ta.selectionEnd);
            ta.focus();
        },

        updatePreview() {
            const text = this.getTA().value;
            this.$refs.preview.innerHTML = this.parseBBCode(text);
        },

        parseBBCode(text) {
            if (!text.trim()) return '<span class="text-gray-600 italic">Preview...</span>';

            // Escape HTML
            text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

            // BBCode to HTML
            const rules = [
                [/\[b\](.*?)\[\/b\]/gis, '<strong>$1</strong>'],
                [/\[i\](.*?)\[\/i\]/gis, '<em>$1</em>'],
                [/\[u\](.*?)\[\/u\]/gis, '<span class="underline">$1</span>'],
                [/\[s\](.*?)\[\/s\]/gis, '<span class="line-through">$1</span>'],
                [/\[color=(#[0-9a-fA-F]{3,6}|[a-zA-Z]+)\](.*?)\[\/color\]/gis, '<span style="color:$1">$2</span>'],
                [/\[size=([0-9]+)\](.*?)\[\/size\]/gis, '<span style="font-size:$1px">$2</span>'],
                [/\[url=(https?:\/\/[^\]]+)\](.*?)\[\/url\]/gis, '<a href="$1" class="text-blue-400 underline" target="_blank">$2</a>'],
                [/\[url\](https?:\/\/[^\[]+)\[\/url\]/gis, '<a href="$1" class="text-blue-400 underline" target="_blank">$1</a>'],
                [/\[img\](https?:\/\/[^\[]+)\[\/img\]/gis, '<img src="$1" class="max-w-full rounded-lg my-2" loading="lazy">'],
                [/\[code\](.*?)\[\/code\]/gis, '<code class="bg-gray-900 text-green-400 px-2 py-1 rounded text-sm font-mono">$1</code>'],
                [/\[codeblock\](.*?)\[\/codeblock\]/gis, '<pre class="bg-gray-900 border border-gray-700 rounded-lg p-4 my-2"><code class="text-green-400 text-sm font-mono">$1</code></pre>'],
                [/\[quote=(.*?)\](.*?)\[\/quote\]/gis, '<blockquote class="border-l-4 border-blue-500 bg-gray-900/50 pl-4 py-2 my-2"><div class="text-blue-400 text-sm font-semibold mb-1">$1:</div><div class="text-gray-400 italic">$2</div></blockquote>'],
                [/\[quote\](.*?)\[\/quote\]/gis, '<blockquote class="border-l-4 border-blue-500 bg-gray-900/50 pl-4 py-2 my-2 text-gray-400 italic">$1</blockquote>'],
                [/\[spoiler=(.*?)\](.*?)\[\/spoiler\]/gis, '<details class="bg-gray-900/50 border border-gray-700 rounded-lg my-2"><summary class="cursor-pointer px-4 py-2 text-gray-400">$1</summary><div class="px-4 py-2 text-gray-300">$2</div></details>'],
                [/\[spoiler\](.*?)\[\/spoiler\]/gis, '<details class="bg-gray-900/50 border border-gray-700 rounded-lg my-2"><summary class="cursor-pointer px-4 py-2 text-gray-400">Spoiler</summary><div class="px-4 py-2 text-gray-300">$1</div></details>'],
                [/\[\*\](.*?)(?=\[\*\]|\[\/list\]|\n|$)/gis, '<li class="text-gray-300">$1</li>'],
                [/\[list=1\](.*?)\[\/list\]/gis, '<ol class="list-decimal list-inside my-2 space-y-1">$1</ol>'],
                [/\[list\](.*?)\[\/list\]/gis, '<ul class="list-disc list-inside my-2 space-y-1">$1</ul>'],
                [/\[hr\]/gi, '<hr class="border-gray-700 my-4">'],
                [/\[youtube\](?:https?:\/\/(?:www\.)?youtube\.com\/watch\?v=|https?:\/\/youtu\.be\/)([a-zA-Z0-9_-]+)(?:[^\[]*)\[\/youtube\]/gis, '<div class="my-2 aspect-video max-w-lg"><iframe src="https://www.youtube-nocookie.com/embed/$1" class="w-full h-full rounded-lg" frameborder="0" allowfullscreen></iframe></div>'],
                [/\[center\](.*?)\[\/center\]/gis, '<div class="text-center">$1</div>'],
            ];

            rules.forEach(function(r) { text = text.replace(r[0], r[1]); });
            text = text.replace(/\n/g, '<br>');
            return text;
        }
    }
}
</script>
