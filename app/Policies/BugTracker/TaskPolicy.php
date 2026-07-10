<?php

declare(strict_types=1);

namespace App\Policies\BugTracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BugTracker\Task;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_bug::tracker::task');
    }

    public function view(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('view_bug::tracker::task');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_bug::tracker::task');
    }

    public function update(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('update_bug::tracker::task');
    }

    public function delete(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('delete_bug::tracker::task');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_bug::tracker::task');
    }

    public function restore(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('restore_bug::tracker::task');
    }

    public function forceDelete(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('force_delete_bug::tracker::task');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_bug::tracker::task');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_bug::tracker::task');
    }

    public function replicate(AuthUser $authUser, Task $task): bool
    {
        return $authUser->can('replicate_bug::tracker::task');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_bug::tracker::task');
    }

}