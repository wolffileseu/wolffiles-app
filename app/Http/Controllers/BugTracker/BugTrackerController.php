<?php

namespace App\Http\Controllers\BugTracker;

use App\Enums\BugTracker\TaskPriority;
use App\Enums\BugTracker\TaskSeverity;
use App\Enums\BugTracker\TaskStatus;
use App\Enums\BugTracker\TaskType;
use App\Http\Controllers\Controller;
use App\Http\Requests\BugTracker\StoreCommentRequest;
use App\Http\Requests\BugTracker\StoreTaskRequest;
use App\Models\BugTracker\Comment;
use App\Models\BugTracker\Project;
use App\Models\BugTracker\Task;
use App\Services\BugTracker\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BugTrackerController extends Controller
{
    public function __construct(private readonly MarkdownRenderer $markdown) {}

    public function index(): View
    {
        $projects = Project::where('is_active', true)
            ->where('is_public', true)
            ->withCount(['tasks as open_tasks_count' => fn ($q) =>
                $q->whereIn('status', ['new', 'confirmed', 'assigned', 'in_progress'])])
            ->withCount('tasks')
            ->orderBy('sort_order')
            ->get();

        $recentTasks = Task::with(['project', 'assignee', 'reporter'])
            ->whereHas('project', fn ($q) => $q->where('is_public', true)->where('is_active', true))
            ->orderByDesc('last_activity_at')
            ->limit(15)
            ->get();

        return view('bug.index', compact('projects', 'recentTasks'));
    }

    public function project(string $slug, Request $request): View
    {
        $project = Project::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $query = $project->tasks()->with(['assignee', 'reporter', 'category']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        } elseif (! $request->has('all')) {
            $query->whereIn('status', ['new', 'confirmed', 'assigned', 'in_progress']);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->query('q')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tasks = $query->orderByDesc('last_activity_at')->paginate(25)->withQueryString();

        return view('bug.project', [
            'project'     => $project,
            'tasks'       => $tasks,
            'statuses'    => TaskStatus::cases(),
            'priorities'  => TaskPriority::cases(),
            'types'       => TaskType::cases(),
            'filters'     => $request->only(['status', 'priority', 'type', 'q', 'all']),
        ]);
    }

    public function show(string $projectSlug, int $number): View
    {
        $project = Project::where('slug', $projectSlug)->where('is_public', true)->firstOrFail();

        $task = $project->tasks()
            ->where('task_number', $number)
            ->with(['reporter', 'assignee', 'category', 'tags',
                    'comments' => fn ($q) => $q->where('is_internal', false)->with('user')->orderBy('created_at'),
                    'history.user'])
            ->firstOrFail();

        // increment views (without triggering observers for unrelated fields)
        $task->increment('views_count');

        $descriptionHtml = $this->markdown->render($task->description);

        return view('bug.show', [
            'project'         => $project,
            'task'            => $task,
            'descriptionHtml' => $descriptionHtml,
            'markdown'        => $this->markdown,
        ]);
    }

    public function create(string $projectSlug = null): View
    {
        $projects = Project::where('is_active', true)->where('is_public', true)->orderBy('sort_order')->get();
        $selected = $projectSlug ? Project::where('slug', $projectSlug)->first() : null;

        return view('bug.create', [
            'projects'    => $projects,
            'selected'    => $selected,
            'types'       => TaskType::cases(),
            'severities'  => TaskSeverity::cases(),
            'priorities'  => TaskPriority::cases(),
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $project = Project::where('slug', $data['project_slug'])
            ->where('is_public', true)
            ->where('is_active', true)
            ->firstOrFail();

        $task = Task::create([
            'project_id'    => $project->id,
            'title'         => $data['title'],
            'description'   => $data['description'],
            'type'          => $data['type']     ?? 'bug',
            'severity'      => $data['severity'] ?? 'minor',
            'priority'      => $data['priority'] ?? 'normal',
            'status'        => 'new',
            'reporter_id'   => auth()->id(),
            'affected_version' => $data['affected_version'] ?? null,
        ]);

        return redirect()
            ->route('bug.show', ['projectSlug' => $project->slug, 'number' => $task->task_number])
            ->with('status', 'Bug report submitted. Thanks!');
    }

    public function comment(string $projectSlug, int $number, StoreCommentRequest $request)
    {
        $project = Project::where('slug', $projectSlug)->where('is_public', true)->firstOrFail();
        $task = $project->tasks()->where('task_number', $number)->firstOrFail();

        Comment::create([
            'task_id'     => $task->id,
            'user_id'     => auth()->id(),
            'body'        => $request->validated()['body'],
            'is_internal' => false,
        ]);

        return redirect()
            ->route('bug.show', ['projectSlug' => $project->slug, 'number' => $task->task_number])
            ->with('status', 'Comment added.');
    }
}
