<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ForumCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_forum::category');
    }

    public function view(AuthUser $authUser, ForumCategory $forumCategory): bool
    {
        return $authUser->can('view_forum::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_forum::category');
    }

    public function update(AuthUser $authUser, ForumCategory $forumCategory): bool
    {
        return $authUser->can('update_forum::category');
    }

    public function delete(AuthUser $authUser, ForumCategory $forumCategory): bool
    {
        return $authUser->can('delete_forum::category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_forum::category');
    }

    public function restore(AuthUser $authUser, ForumCategory $forumCategory): bool
    {
        return $authUser->can('restore_forum::category');
    }

    public function forceDelete(AuthUser $authUser, ForumCategory $forumCategory): bool
    {
        return $authUser->can('force_delete_forum::category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_forum::category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_forum::category');
    }

    public function replicate(AuthUser $authUser, ForumCategory $forumCategory): bool
    {
        return $authUser->can('replicate_forum::category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_forum::category');
    }

}