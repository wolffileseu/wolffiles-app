<?php

namespace App\Http\Requests\BugTracker;

use App\Enums\BugTracker\TaskPriority;
use App\Enums\BugTracker\TaskSeverity;
use App\Enums\BugTracker\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'project_slug'     => 'required|string|exists:bt_projects,slug',
            'title'            => 'required|string|min:3|max:255',
            'description'      => 'required|string|min:10|max:20000',
            'type'             => ['nullable', new Enum(TaskType::class)],
            'severity'         => ['nullable', new Enum(TaskSeverity::class)],
            'priority'         => ['nullable', new Enum(TaskPriority::class)],
            'affected_version' => 'nullable|string|max:50',
        ];
    }
}
