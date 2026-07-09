<?php

declare(strict_types=1);

namespace App\Policies\BugTracker;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BugTracker\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_bug::tracker::category');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('view_bug::tracker::category');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_bug::tracker::category');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('update_bug::tracker::category');
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('delete_bug::tracker::category');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_bug::tracker::category');
    }

    public function restore(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('restore_bug::tracker::category');
    }

    public function forceDelete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('force_delete_bug::tracker::category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_bug::tracker::category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_bug::tracker::category');
    }

    public function replicate(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('replicate_bug::tracker::category');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_bug::tracker::category');
    }

}