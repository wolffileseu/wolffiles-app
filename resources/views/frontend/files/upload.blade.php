<x-layouts.app title="Upload File">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-2">Upload a File</h1>
        <p class="text-gray-400 mb-8">Upload a file to share with the community. Our team will review it before publishing.</p>

        <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data"
              class="bg-gray-800 rounded-lg border border-gray-700 p-8 space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                @error('title') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea name="description" rows="5"
                          class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-amber-500 focus:border-amber-500">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Category *</label>
                    <select name="category_id" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                        <option value="">Select category...</option>
                        @foreach($categories->groupBy(fn ($c) => $c->parent?->name ?? $c->name) as $group => $cats)
                            <optgroup label="{{ $group }}">
                                @foreach($cats as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->parent ? $cat->name : '— ' . $cat->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Game</label>
                    <select name="game" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                        <option value="">Auto-detect</option>
                        <option value="ET" {{ old('game') == 'ET' ? 'selected' : '' }}>Enemy Territory</option>
                        <option value="RtCW" {{ old('game') == 'RtCW' ? 'selected' : '' }}>RtCW</option>
                        <option value="ET Quake Wars" {{ old('game') == 'ET Quake Wars' ? 'selected' : '' }}>ET Quake Wars</option>
                        <option value="Wolf Classic" {{ old('game') == 'Wolf Classic' ? 'selected' : '' }}>Wolf Classic</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Version</label>
                    <input type="text" name="version" value="{{ old('version') }}" placeholder="e.g. 1.0, final, beta2"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Original Author</label>
                    <input type="text" name="original_author" value="{{ old('original_author') }}" placeholder="Map/Mod creator"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                </div>
            </div>

            {{-- Tags Section --}}
            <div x-data="{
                selectedTags: {{ json_encode(old('tags', [])) }},
                customTag: '',
                predefinedTags: [
                    { group: 'Map Type', tags: ['Objective', 'Frag', 'Trickjump', 'Deathmatch', 'CTF', 'Last Man Standing', 'Single Player'] },
                    { group: 'Style', tags: ['Sniper', 'Panzer', 'Rifle', 'SMG', 'CQB', 'Vehicle', 'Indoor', 'Outdoor'] },
                    { group: 'Size', tags: ['Small (2-6)', 'Medium (6-12)', 'Large (12-24)', 'XL (24+)'] },
                    { group: 'Theme', tags: ['WW2', 'Desert', 'Snow', 'Urban', 'Forest', 'Beach', 'Night', 'Custom'] },
                    { group: 'Quality', tags: ['Final', 'Beta', 'Alpha', 'Fun Map', 'Competitive', 'Tournament'] },
                ],
                toggleTag(tag) {
                    const idx = this.selectedTags.indexOf(tag);
                    if (idx > -1) {
                        this.selectedTags.splice(idx, 1);
                    } else {
                        this.selectedTags.push(tag);
                    }
                },
                addCustomTag() {
                    const tag = this.customTag.trim();
                    if (tag && !this.selectedTags.includes(tag)) {
                        this.selectedTags.push(tag);
                    }
                    this.customTag = '';
                },
                removeTag(tag) {
                    this.selectedTags = this.selectedTags.filter(t => t !== tag);
                }
            }">
                <label class="block text-sm font-medium text-gray-300 mb-2">Tags</label>
                <p class="text-gray-500 text-sm mb-3">Select tags that describe this file. This helps other players find it.</p>

                {{-- Selected Tags Display --}}
                <div class="flex flex-wrap gap-2 mb-4 min-h-[2rem]" x-show="selectedTags.length > 0">
                    <template x-for="tag in selectedTags" :key="tag">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-amber-600/20 text-amber-400 border border-amber-600/30">
                            <span x-text="tag"></span>
                            <button type="button" @click="removeTag(tag)" class="hover:text-red-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>

                {{-- Hidden inputs for form submission --}}
                <template x-for="tag in selectedTags" :key="'input-' + tag">
                    <input type="hidden" name="tags[]" :value="tag">
                </template>

                {{-- Predefined Tag Groups --}}
                <div class="space-y-3 mb-4">
                    <template x-for="group in predefinedTags" :key="group.group">
                        <div>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider" x-text="group.group"></span>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <template x-for="tag in group.tags" :key="tag">
                                    <button type="button"
                                            @click="toggleTag(tag)"
                                            :class="selectedTags.includes(tag)
                                                ? 'bg-amber-600 text-white border-amber-500'
                                                : 'bg-gray-700 text-gray-300 border-gray-600 hover:border-amber-500 hover:text-amber-400'"
                                            class="px-3 py-1 rounded-full text-sm border transition-all duration-150">
                                        <span x-text="tag"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Custom Tag Input --}}
                <div class="flex gap-2">
                    <input type="text" x-model="customTag"
                           @keydown.enter.prevent="addCustomTag()"
                           placeholder="Add custom tag..."
                           class="flex-1 bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm focus:ring-amber-500 focus:border-amber-500">
                    <button type="button" @click="addCustomTag()"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg text-sm transition-colors">
                        Add
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">File * (max {{ config('app.max_upload_size', 500) }}MB)</label>
                <input type="file" name="file" required
                       class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-amber-600 file:text-white file:cursor-pointer">
                <p class="text-gray-500 text-sm mt-1">Supported: .pk3, .zip, .rar, .7z, .lua, .cfg, .txt</p>
                @error('file') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Screenshots (optional, max 10)</label>
                <input type="file" name="screenshots[]" multiple accept="image/*"
                       class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-3">
                <p class="text-gray-500 text-sm mt-1">Images will also be auto-extracted from PK3/ZIP files.</p>
            </div>

            <div class="bg-gray-750 rounded-lg p-4 border border-gray-600">
                <p class="text-gray-400 text-sm">
                    ℹ️ After uploading, our team will review your file. We'll scan for viruses, verify the content,
                    and may adjust the description or images before publishing. You'll be notified once it's approved.
                </p>
            </div>

            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-4 rounded-lg font-bold text-lg transition-colors">
                Upload File
            </button>
        </form>
    </div>
</x-layouts.app>
