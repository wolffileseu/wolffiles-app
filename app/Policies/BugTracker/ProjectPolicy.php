<?php

declare(strict_types=1);

namespace App\Policies\BugTracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BugTracker\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_bug::tracker::project');
    }

    public function view(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('view_bug::tracker::project');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_bug::tracker::project');
    }

    public function update(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('update_bug::tracker::project');
    }

    public function delete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('delete_bug::tracker::project');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_bug::tracker::project');
    }

    public function restore(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('restore_bug::tracker::project');
    }

    public function forceDelete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('force_delete_bug::tracker::project');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_bug::tracker::project');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_bug::tracker::project');
    }

    public function replicate(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('replicate_bug::tracker::project');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_bug::tracker::project');
    }

}