<x-filament-panels::page>
    @php
        $articles = $this->getArticles();
        $currentArticle = $this->getArticle();
        $categories = \App\Models\KnowledgeBase::categories();
    @endphp

    <div x-data="{ showSidebar: window.innerWidth >= 1024 }" class="flex flex-col lg:flex-row gap-4 lg:gap-6" style="min-height: 600px;">

        {{-- Mobile Toggle --}}
        <div class="lg:hidden flex gap-2">
            <button @click="showSidebar = !showSidebar"
                class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg text-sm">
                <span x-text="showSidebar ? '✕ Close Menu' : '📚 Browse Articles'"></span>
            </button>
            @if($currentArticle && !$editing && !$creating)
            <button wire:click="$set('selectedArticle', null)"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm">← Back</button>
            @endif
        </div>

        {{-- Sidebar --}}
        <div x-show="showSidebar || window.innerWidth >= 1024" x-cloak
            class="w-full lg:w-72 flex-shrink-0">

            {{-- Search --}}
            <div class="mb-3">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="🔍 Search docs..."
                    class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 text-sm">
            </div>

            {{-- New Article --}}
            <button wire:click="startCreate" @click="showSidebar = false"
                class="w-full px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 mb-3">
                ✏️ New Article
            </button>

            {{-- Category Tabs --}}
            <div class="flex flex-wrap gap-1 mb-3 lg:flex-col lg:space-y-1 lg:gap-0">
                <button wire:click="$set('selectedCategory', null)"
                    class="px-3 py-1.5 lg:py-2 rounded-lg text-xs lg:text-sm transition lg:w-full lg:text-left {{ !$selectedCategory ? 'bg-primary-500/20 text-primary-400 font-semibold' : 'bg-gray-700/30 lg:bg-transparent text-gray-400 hover:bg-gray-700/50' }}">
                    📚 All ({{ \App\Models\KnowledgeBase::count() }})
                </button>
                @foreach($categories as $key => $label)
                <button wire:click="$set('selectedCategory', '{{ $key }}')"
                    class="px-3 py-1.5 lg:py-2 rounded-lg text-xs lg:text-sm transition lg:w-full lg:text-left {{ $selectedCategory === $key ? 'bg-primary-500/20 text-primary-400 font-semibold' : 'bg-gray-700/30 lg:bg-transparent text-gray-400 hover:bg-gray-700/50' }}">
                    {{ $label }} ({{ \App\Models\KnowledgeBase::where('category', $key)->count() }})
                </button>
                @endforeach
            </div>

            {{-- Article List --}}
            <div class="space-y-1 max-h-64 lg:max-h-96 overflow-y-auto">
                @forelse($articles as $article)
                <button wire:click="selectArticle({{ $article->id }})" @click="showSidebar = window.innerWidth >= 1024"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm transition {{ $selectedArticle === $article->id ? 'bg-amber-500/20 text-amber-400 font-semibold' : 'text-gray-300 hover:bg-gray-700/50' }}">
                    <div class="flex items-center gap-2">
                        <span>{{ $article->icon }}</span>
                        <span class="truncate">{{ $article->title }}</span>
                        @if($article->is_pinned)
                        <span class="text-xs">📌</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 ml-6 hidden lg:block">{{ $categories[$article->category] ?? '' }}</div>
                </button>
                @empty
                <div class="text-gray-500 text-sm px-3 py-4">No articles yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Content Area --}}
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
             x-show="!showSidebar || window.innerWidth >= 1024 || {{ $editing || $creating || $selectedArticle ? 'true' : 'false' }}">

            @if($editing || $creating)
                {{-- Editor --}}
                <div class="p-4 lg:p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-3 mb-4">
                        <h2 class="text-lg lg:text-xl font-bold">{{ $creating ? '✏️ New Article' : '✏️ Edit Article' }}</h2>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button wire:click="cancelEdit" class="flex-1 sm:flex-none px-4 py-2 bg-gray-600 text-white rounded-lg text-sm">Cancel</button>
                            <button wire:click="save" class="flex-1 sm:flex-none px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">💾 Save</button>
                        </div>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Title *</label>
                            <input type="text" wire:model="editTitle"
                                class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Category</label>
                                <select wire:model="editCategory" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 text-sm">
                                    @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Icon</label>
                                <input type="text" wire:model="editIcon" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Sort</label>
                                <input type="number" wire:model="editSortOrder" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="flex items-end pb-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="editPinned" class="rounded bg-gray-700 border-gray-600 text-primary-500">
                                    <span class="text-xs text-gray-300">📌 Pin</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Content</label>
                        <div
                            x-data="{
                                content: @entangle('editContent'),
                                init() {
                                    const el = this.$refs.editor;
                                    el.innerHTML = this.content;
                                    el.addEventListener('input', () => {
                                        this.content = el.innerHTML;
                                    });
                                }
                            }"
                        >
                            {{-- Toolbar --}}
                            <div class="flex flex-wrap gap-1 bg-gray-900 border border-gray-700 border-b-0 rounded-t-lg p-2">
                                <button type="button" onclick="document.execCommand('bold')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600 font-bold">B</button>
                                <button type="button" onclick="document.execCommand('italic')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600 italic">I</button>
                                <button type="button" onclick="document.execCommand('underline')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600 underline">U</button>
                                <span class="text-gray-600 hidden sm:inline">|</span>
                                <button type="button" onclick="document.execCommand('formatBlock', false, 'h2')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">H2</button>
                                <button type="button" onclick="document.execCommand('formatBlock', false, 'h3')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">H3</button>
                                <button type="button" onclick="document.execCommand('formatBlock', false, 'p')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">P</button>
                                <span class="text-gray-600 hidden sm:inline">|</span>
                                <button type="button" onclick="document.execCommand('insertUnorderedList')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">• List</button>
                                <button type="button" onclick="document.execCommand('insertOrderedList')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">1.</button>
                                <span class="text-gray-600 hidden sm:inline">|</span>
                                <button type="button" onclick="var url=prompt('URL:');if(url)document.execCommand('createLink',false,url)" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">🔗</button>
                                <button type="button" onclick="document.execCommand('formatBlock', false, 'pre')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">⌨️</button>
                                <button type="button" onclick="document.execCommand('insertHorizontalRule')" class="px-2 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600">—</button>
                            </div>

                            <div x-ref="editor" contenteditable="true"
                                class="w-full bg-gray-700 border border-gray-600 text-white rounded-b-lg px-4 lg:px-6 py-4 min-h-[250px] lg:min-h-[400px] max-h-[500px] overflow-y-auto prose prose-invert prose-sm max-w-none focus:outline-none focus:ring-2 focus:ring-primary-500"
                                style="line-height: 1.8;">
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($currentArticle)
                {{-- Article View --}}
                <div class="p-4 lg:p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-3 mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xl lg:text-2xl">{{ $currentArticle->icon }}</span>
                                <h1 class="text-lg lg:text-2xl font-bold text-white">{{ $currentArticle->title }}</h1>
                                @if($currentArticle->is_pinned)<span class="text-amber-400">📌</span>@endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>{{ $categories[$currentArticle->category] ?? '' }}</span>
                                <span>·</span>
                                <span>{{ $currentArticle->updated_at->format('d.m.Y H:i') }}</span>
                                @if($currentArticle->editor)
                                <span>· by {{ $currentArticle->editor->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="startEdit" class="px-3 py-1.5 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600">✏️ Edit</button>
                            <button wire:click="deleteArticle" wire:confirm="Delete this article?"
                                class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600">🗑️</button>
                        </div>
                    </div>

                    <div class="prose prose-invert prose-sm max-w-none overflow-x-auto">
                        <style>
                            .prose pre { background: #1e293b; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.75rem; line-height: 1.6; }
                            .prose code { background: #334155; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.8em; }
                            .prose pre code { background: none; padding: 0; }
                            .prose h2 { color: #fbbf24; border-bottom: 1px solid #374151; padding-bottom: 0.5rem; margin-top: 2rem; }
                            .prose h3 { color: #60a5fa; margin-top: 1.5rem; }
                            .prose a { color: #f59e0b; }
                            .prose ul, .prose ol { padding-left: 1.5rem; }
                            .prose li { margin: 0.25rem 0; }
                        </style>
                        {!! $currentArticle->content !!}
                    </div>
                </div>

            @else
                {{-- Welcome --}}
                <div class="flex flex-col items-center justify-center h-full text-center p-6 lg:p-12">
                    <div class="text-5xl lg:text-6xl mb-4">📚</div>
                    <h2 class="text-xl lg:text-2xl font-bold text-white mb-2">Wolffiles Knowledge Base</h2>
                    <p class="text-gray-400 mb-6 text-sm max-w-md">Internal docs, commands, feature guides, changelogs and troubleshooting.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-lg">
                        @foreach($categories as $key => $label)
                        <button wire:click="$set('selectedCategory', '{{ $key }}')"
                            class="p-4 bg-gray-700/50 rounded-xl hover:bg-gray-700 transition text-left">
                            <div class="text-sm lg:text-lg font-semibold text-white">{{ $label }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ \App\Models\KnowledgeBase::where('category', $key)->count() }} articles</div>
                        </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
