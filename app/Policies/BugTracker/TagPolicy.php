<?php

declare(strict_types=1);

namespace App\Policies\BugTracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BugTracker\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_bug::tracker::tag');
    }

    public function view(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('view_bug::tracker::tag');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_bug::tracker::tag');
    }

    public function update(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('update_bug::tracker::tag');
    }

    public function delete(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('delete_bug::tracker::tag');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_bug::tracker::tag');
    }

    public function restore(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('restore_bug::tracker::tag');
    }

    public function forceDelete(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('force_delete_bug::tracker::tag');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_bug::tracker::tag');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_bug::tracker::tag');
    }

    public function replicate(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('replicate_bug::tracker::tag');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_bug::tracker::tag');
    }

}