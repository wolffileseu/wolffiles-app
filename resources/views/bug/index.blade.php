<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
            🐛 Bug Tracker
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

            <!-- Project Tiles -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Projects</h3>
                    @auth
                        <a href="{{ route('bug.create') }}"
                           class="inline-flex items-center gap-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            + Report Bug
                        </a>
                    @endauth
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($projects as $project)
                        <a href="{{ route('bug.project', $project->slug) }}"
                           class="block p-5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-indigo-400 hover:shadow-md transition">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ $project->icon ?: '📁' }}</span>
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ $project->name }}</h4>
                                </div>
                                <span class="inline-block w-3 h-3 rounded-full" style="background-color: {{ $project->color }}"></span>
                            </div>
                            @if($project->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ $project->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span><strong class="text-amber-600 dark:text-amber-400">{{ $project->open_tasks_count }}</strong> open</span>
                                <span>·</span>
                                <span>{{ $project->tasks_count }} total</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Recent Activity -->
            <section>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Recent Activity</h3>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    @forelse($recentTasks as $task)
                        <a href="{{ route('bug.show', [$task->project->slug, $task->task_number]) }}"
                           class="flex items-center gap-3 p-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <span class="text-lg">{{ $task->type->icon() }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($task->project->slug) }}-{{ $task->task_number }}
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded {{ $statusClasses[$task->status->value] }}">
                                        {{ $task->status->label() }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $task->title }}</p>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $task->last_activity_at?->diffForHumans() }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">No tasks yet. Be the first to report one!</p>
                    @endforelse
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
