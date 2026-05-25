<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
            <a href="{{ route('bug.index') }}" class="text-gray-500 hover:text-indigo-600">🐛 Bug Tracker</a>
            <span class="text-gray-400">/</span>
            <span>Report a Bug</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('bug.store') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project *</label>
                    <select name="project_slug" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                        @foreach($projects as $p)
                            <option value="{{ $p->slug }}" @selected(old('project_slug', $selected?->slug) === $p->slug)>{{ $p->icon }} {{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('project_slug') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                    <input type="text" name="title" required minlength="3" maxlength="255" value="{{ old('title') }}"
                           placeholder="Short, descriptive summary"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                    @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                    <textarea name="description" rows="10" required minlength="10" maxlength="20000"
                              placeholder="Steps to reproduce&#10;Expected behavior&#10;Actual behavior&#10;&#10;Markdown supported."
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm font-mono">{{ old('description') }}</textarea>
                    @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            @foreach($types as $t)
                                <option value="{{ $t->value }}" @selected(old('type', 'bug') === $t->value)>{{ $t->icon() }} {{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Severity</label>
                        <select name="severity" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            @foreach($severities as $s)
                                <option value="{{ $s->value }}" @selected(old('severity', 'minor') === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                        <select name="priority" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                            @foreach($priorities as $p)
                                <option value="{{ $p->value }}" @selected(old('priority', 'normal') === $p->value)>{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Affected Version (optional)</label>
                    <input type="text" name="affected_version" maxlength="50" value="{{ old('affected_version') }}"
                           placeholder="e.g. v1.2.3"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('bug.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
