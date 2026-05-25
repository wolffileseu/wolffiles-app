<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
            <a href="{{ route('bug.index') }}" class="text-gray-500 hover:text-indigo-600">🐛 Bug Tracker</a>
            <span class="text-gray-400">/</span>
            <span>{{ $project->icon }} {{ $project->name }}</span>
        </h2>
    </x-slot>

    @php
    $statusClasses = [
        'new'         => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'confirmed'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'assigned'    => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        'in_progress' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'fixed'       => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'closed'      => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'wontfix'     => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        'duplicate'   => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'invalid'     => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    ];
    $priorityClasses = [
        'very_low'    => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'low'         => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'normal'      => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'high'        => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'urgent'      => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ];
@endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <!-- Filters + Action -->
            <form method="GET" class="flex flex-wrap items-end gap-3 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title…"
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="">Open only</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Priority</label>
                    <select name="priority" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="">All</option>
                        @foreach($priorities as $p)
                            <option value="{{ $p->value }}" @selected(($filters['priority'] ?? '') === $p->value)>{{ $p->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                    <select name="type" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <option value="">All</option>
                        @foreach($types as $t)
                            <option value="{{ $t->value }}" @selected(($filters['type'] ?? '') === $t->value)>{{ $t->icon() }} {{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="all" value="1" @checked(isset($filters['all'])) class="rounded">
                    Show closed
                </label>
                <button type="submit" class="px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm rounded-md">Filter</button>
                @auth
                    <a href="{{ route('bug.create', $project->slug) }}"
                       class="ms-auto px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        + Report Bug
                    </a>
                @endauth
            </form>

            <!-- Task List -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                @forelse($tasks as $task)
                    <a href="{{ route('bug.show', [$project->slug, $task->task_number]) }}"
                       class="flex items-center gap-3 p-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <span class="text-lg">{{ $task->type->icon() }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                    {{ strtoupper($project->slug) }}-{{ $task->task_number }}
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded {{ $statusClasses[$task->status->value] }}">
                                    {{ $task->status->label() }}
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded {{ $priorityClasses[$task->priority->value] }}">
                                    {{ $task->priority->label() }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $task->title }}</p>
                        </div>
                        <div class="text-xs text-gray-400 whitespace-nowrap text-right">
                            @if($task->assignee)
                                <div>👤 {{ $task->assignee->name }}</div>
                            @endif
                            <div>{{ $task->last_activity_at?->diffForHumans() }}</div>
                        </div>
                    </a>
                @empty
                    <p class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">No tasks match your filters.</p>
                @endforelse
            </div>

            {{ $tasks->links() }}

        </div>
    </div>
</x-app-layout>
