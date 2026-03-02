<x-layouts.app title="Tutorial erstellen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">🎓 Neues Tutorial erstellen</h1>

        <form action="{{ route('tutorials.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">
                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Titel *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                           placeholder="z.B. Deine erste ET Map erstellen mit GTKRadiant">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Kategorie *</label>
                        <select name="tutorial_category_id" required
                                class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                            <option value="">— Kategorie wählen —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('tutorial_category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Schwierigkeit *</label>
                        <select name="difficulty" required
                                class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                            <option value="beginner" {{ old('difficulty') == 'beginner' ? 'selected' : '' }}>🟢 Anfänger</option>
                            <option value="intermediate" {{ old('difficulty') == 'intermediate' ? 'selected' : '' }}>🟡 Fortgeschritten</option>
                            <option value="advanced" {{ old('difficulty') == 'advanced' ? 'selected' : '' }}>🔴 Experte</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Dauer (Minuten)</label>
                        <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes') }}" min="1"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                               placeholder="z.B. 30">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Voraussetzungen</label>
                    <input type="text" name="prerequisites" value="{{ old('prerequisites') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                           placeholder="z.B. GTKRadiant installiert, grundlegende Mapping-Kenntnisse">
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">YouTube Video URL (optional)</label>
                    <input type="url" name="youtube_url" value="{{ old('youtube_url') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                           placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Inhalt *</label>
                    <textarea name="content" id="tutorial-content" rows="20" required
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 font-mono text-sm">{{ old('content') }}</textarea>
                    <p class="text-gray-500 text-xs mt-1">HTML und Markdown werden unterstützt. Bilder per URL einfügen.</p>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Tags</label>
                    <input type="text" name="tags" value="{{ old('tags') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                           placeholder="z.B. Mapping, GTKRadiant, ET (kommagetrennt)">
                </div>
            </div>

            @if($errors->any())
                <div class="bg-red-900/50 border border-red-700 rounded-lg p-4">
                    @foreach($errors->all() as $error)
                        <p class="text-red-400 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between">
                <a href="{{ route('tutorials.index') }}" class="text-gray-400 hover:text-white text-sm">← Zurück zu Tutorials</a>
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                    Tutorial einreichen
                </button>
            </div>

            <p class="text-gray-500 text-xs text-center">
                Dein Tutorial wird nach Überprüfung durch einen Moderator veröffentlicht.
            </p>
        </form>
    </div>
</x-layouts.app>
