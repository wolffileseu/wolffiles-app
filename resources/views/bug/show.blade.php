<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2 flex-wrap">
            <a href="{{ route('bug.index') }}" class="text-gray-500 hover:text-indigo-600">🐛 Bug Tracker</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('bug.project', $project->slug) }}" class="text-gray-500 hover:text-indigo-600">{{ $project->icon }} {{ $project->name }}</a>
            <span class="text-gray-400">/</span>
            <span class="font-mono text-gray-500">{{ strtoupper($project->slug) }}-{{ $task->task_number }}</span>
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
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="p-3 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Title block -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <div class="flex items-start justify-between gap-4 mb-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 leading-tight flex items-center gap-2">
                        <span>{{ $task->type->icon() }}</span>
                        <span>{{ $task->title }}</span>
                    </h1>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs px-2 py-1 rounded {{ $statusClasses[$task->status->value] }}">{{ $task->status->label() }}</span>
                        <span class="text-xs px-2 py-1 rounded {{ $priorityClasses[$task->priority->value] }}">{{ $task->priority->label() }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs text-gray-600 dark:text-gray-400 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div><strong class="block text-gray-500 dark:text-gray-500">Reporter</strong>{{ $task->reporter?->name ?? $task->reporter_name ?? 'Anonymous' }}</div>
                    <div><strong class="block text-gray-500 dark:text-gray-500">Assignee</strong>{{ $task->assignee?->name ?? '—' }}</div>
                    <div><strong class="block text-gray-500 dark:text-gray-500">Severity</strong>{{ $task->severity->label() }}</div>
                    <div><strong class="block text-gray-500 dark:text-gray-500">Created</strong>{{ $task->created_at->diffForHumans() }}</div>
                    @if($task->affected_version)
                        <div><strong class="block text-gray-500 dark:text-gray-500">Affected Version</strong>{{ $task->affected_version }}</div>
                    @endif
                    @if($task->target_version)
                        <div><strong class="block text-gray-500 dark:text-gray-500">Target Version</strong>{{ $task->target_version }}</div>
                    @endif
                </div>

                <div class="prose prose-sm dark:prose-invert max-w-none">
                    {!! $descriptionHtml !!}
                </div>

                @if($task->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($task->tags as $tag)
                            <span class="text-xs px-2 py-1 rounded" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Comments -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <h2 class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 text-lg font-semibold text-gray-800 dark:text-gray-200">
                    💬 Comments ({{ $task->comments->count() }})
                </h2>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($task->comments as $comment)
                        <article class="p-6">
                            <div class="flex items-center justify-between mb-2 text-sm">
                                <strong class="text-gray-800 dark:text-gray-200">{{ $comment->user?->name ?? $comment->author_name ?? 'Anonymous' }}</strong>
                                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}@if($comment->edited_at) · edited @endif</span>
                            </div>
                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                {!! $markdown->render($comment->body) !!}
                            </div>
                        </article>
                    @empty
                        <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">No comments yet.</p>
                    @endforelse
                </div>

                <!-- Comment form -->
                @auth
                    <form method="POST" action="{{ route('bug.comment', [$project->slug, $task->task_number]) }}" class="p-6 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add a comment</label>
                        <textarea name="body" rows="4" required minlength="2" maxlength="10000"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 text-sm"
                                  placeholder="Markdown supported…">{{ old('body') }}</textarea>
                        @error('body') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                                Post Comment
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-6 border-t border-gray-100 dark:border-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
                        <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Log in</a> to comment.
                    </div>
                @endauth
            </div>

            <!-- History (collapsed) -->
            @if($task->history->isNotEmpty())
                <details class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <summary class="px-6 py-4 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                        📜 History ({{ $task->history->count() }} events)
                    </summary>
                    <div class="px-6 pb-4 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                        @foreach($task->history as $h)
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 whitespace-nowrap">{{ $h->created_at->diffForHumans() }}</span>
                                <span class="font-medium">{{ $h->user?->name ?? 'System' }}</span>
                                <span>changed</span>
                                <code class="text-indigo-600 dark:text-indigo-400">{{ $h->field }}</code>
                                @if($h->old_value)
                                    <span>from <code class="text-rose-600 dark:text-rose-400">{{ $h->old_value }}</code></span>
                                @endif
                                @if($h->new_value)
                                    <span>to <code class="text-green-600 dark:text-green-400">{{ $h->new_value }}</code></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

        </div>
    </div>
</x-app-layout>
