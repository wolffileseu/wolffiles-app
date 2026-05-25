<?php

namespace App\Observers\BugTracker;

use App\Models\BugTracker\Task;
use App\Models\BugTracker\TaskHistory;

class TaskObserver
{
    /**
     * Tracked fields: changes here are recorded in bt_task_history.
     */
    protected array $tracked = [
        'status', 'priority', 'severity', 'type',
        'assignee_id', 'category_id', 'title',
        'affected_version', 'target_version', 'due_date',
    ];

    public function creating(Task $task): void
    {
        // Auto-increment task_number per project
        if (empty($task->task_number)) {
            $max = Task::where('project_id', $task->project_id)->max('task_number');
            $task->task_number = ($max ?? 0) + 1;
        }

        if (empty($task->last_activity_at)) {
            $task->last_activity_at = now();
        }
    }

    public function created(Task $task): void
    {
        $this->log($task, 'created', null, $task->status?->value);
    }

    public function updating(Task $task): void
    {
        // Set resolved_at / closed_at on status transitions
        if ($task->isDirty('status')) {
            $new = $task->status;
            if ($new && $new->isResolved() && empty($task->resolved_at)) {
                $task->resolved_at = now();
            }
            if ($new?->value === 'closed' && empty($task->closed_at)) {
                $task->closed_at = now();
            }
        }

        $task->last_activity_at = now();
    }

    public function updated(Task $task): void
    {
        foreach ($this->tracked as $field) {
            if ($task->wasChanged($field)) {
                $old = $task->getOriginal($field);
                $new = $task->getAttribute($field);
                $this->log(
                    $task,
                    $field,
                    is_object($old) ? $old->value ?? (string) $old : (string) $old,
                    is_object($new) ? $new->value ?? (string) $new : (string) $new,
                );
            }
        }
    }

    protected function log(Task $task, string $field, ?string $old, ?string $new): void
    {
        TaskHistory::create([
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'field'     => $field,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }
}
